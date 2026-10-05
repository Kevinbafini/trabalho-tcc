<?php

namespace App\Services;

use App\Enums\TipoOperacao;
use App\Models\AtividadeAgricola;
use App\Models\Propriedade;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Regras de negócio da apropriação de custos de uma atividade agrícola.
 *
 * Cálculos:
 *   custo do insumo      = quantidade × custo unitário
 *   custo da máquina     = horas × custo por hora
 *   custo da mão de obra = horas × custo por hora
 *   custo total          = insumos + máquinas + mão de obra
 *
 * Regra de histórico (snapshot): o custo unitário/hora de cada recurso é
 * gravado na tabela de relacionamento no momento do lançamento. Alterar o
 * cadastro do recurso depois não muda o custo das atividades já registradas.
 *
 * Na edição de uma atividade:
 *   - itens que já pertenciam à atividade mantêm o valor gravado originalmente,
 *     mesmo que a quantidade ou as horas sejam alteradas;
 *   - itens novos usam o valor atual do cadastro.
 *
 * @phpstan-type LinhaDeRecurso array{id: int, quantidade: float}
 * @phpstan-type DadosAtividade array{
 *     talhao_id: int,
 *     data: string,
 *     tipo: TipoOperacao,
 *     descricao: string|null,
 *     insumos: list<LinhaDeRecurso>,
 *     maquinas: list<LinhaDeRecurso>,
 *     funcionarios: list<LinhaDeRecurso>,
 *     implementos: list<int>,
 * }
 * @phpstan-type ItemCalculado array{quantidade: float, valor: float, custo_total: float}
 */
class CustoAtividadeService
{
    /**
     * Cria ou atualiza a atividade, calculando e gravando todos os custos
     * dentro de uma transação (ou tudo é salvo, ou nada é salvo).
     *
     * Os custos nunca vêm do navegador: são sempre recalculados aqui.
     *
     * @param  DadosAtividade  $dados
     */
    public function salvar(AtividadeAgricola $atividade, Propriedade $propriedade, array $dados): AtividadeAgricola
    {
        return DB::transaction(function () use ($atividade, $propriedade, $dados): AtividadeAgricola {
            $talhao = $propriedade->talhoes()->findOrFail($dados['talhao_id']);

            // A safra é copiada do talhão no lançamento e não acompanha mudanças futuras do talhão.
            if (! $atividade->exists || $atividade->talhao_id !== $talhao->id) {
                $atividade->safra = $talhao->safra;
            }

            $atividade->propriedade()->associate($propriedade);
            $atividade->talhao()->associate($talhao);
            $atividade->fill([
                'data' => $dados['data'],
                'tipo' => $dados['tipo'],
                'descricao' => $dados['descricao'],
            ]);
            $atividade->save();

            // Os itens são calculados ANTES de atualizar as tabelas de relacionamento,
            // para que os valores históricos já gravados possam ser reaproveitados.
            $insumos = $this->calcularInsumos($atividade, $propriedade, $dados['insumos']);
            $maquinas = $this->calcularMaquinas($atividade, $propriedade, $dados['maquinas']);
            $funcionarios = $this->calcularMaoDeObra($atividade, $propriedade, $dados['funcionarios']);

            $atividade->insumos()->sync(array_map(fn (array $item): array => [
                'quantidade' => $item['quantidade'],
                'custo_unitario' => $item['valor'],
                'custo_total' => $item['custo_total'],
            ], $insumos));

            $atividade->maquinas()->sync(array_map(fn (array $item): array => [
                'horas' => $item['quantidade'],
                'custo_hora' => $item['valor'],
                'custo_total' => $item['custo_total'],
            ], $maquinas));

            $atividade->funcionarios()->sync(array_map(fn (array $item): array => [
                'horas' => $item['quantidade'],
                'custo_hora' => $item['valor'],
                'custo_total' => $item['custo_total'],
            ], $funcionarios));

            // Implementos não têm custo próprio: apenas ficam associados à atividade.
            $atividade->implementos()->sync(
                $propriedade->implementos()->whereKey($dados['implementos'])->pluck('id')->all(),
            );

            $atividade->custo_insumos = $this->somar($insumos);
            $atividade->custo_maquinas = $this->somar($maquinas);
            $atividade->custo_mao_obra = $this->somar($funcionarios);
            $atividade->custo_total = round($atividade->custo_insumos + $atividade->custo_maquinas + $atividade->custo_mao_obra, 2);
            $atividade->save();

            return $atividade;
        });
    }

    /**
     * Custo de um item: quantidade (ou horas) × valor unitário (ou valor/hora),
     * arredondado em centavos.
     */
    public function calcularCusto(float $quantidade, float $valorUnitario): float
    {
        return round($quantidade * $valorUnitario, 2);
    }

    /**
     * Custo dos insumos: quantidade × custo unitário.
     *
     * @param  list<LinhaDeRecurso>  $linhas
     * @return array<int, ItemCalculado>
     */
    private function calcularInsumos(AtividadeAgricola $atividade, Propriedade $propriedade, array $linhas): array
    {
        $valoresHistoricos = $atividade->insumos()->pluck('atividade_insumo.custo_unitario', 'insumos.id');
        $valoresAtuais = $propriedade->insumos()->pluck('valor_unitario', 'id');

        return $this->calcularItens($linhas, $valoresHistoricos, $valoresAtuais);
    }

    /**
     * Custo das máquinas: horas × custo por hora.
     *
     * @param  list<LinhaDeRecurso>  $linhas
     * @return array<int, ItemCalculado>
     */
    private function calcularMaquinas(AtividadeAgricola $atividade, Propriedade $propriedade, array $linhas): array
    {
        $valoresHistoricos = $atividade->maquinas()->pluck('atividade_maquina.custo_hora', 'maquinas.id');
        $valoresAtuais = $propriedade->maquinas()->pluck('custo_hora', 'id');

        return $this->calcularItens($linhas, $valoresHistoricos, $valoresAtuais);
    }

    /**
     * Custo da mão de obra: horas trabalhadas × custo por hora.
     *
     * @param  list<LinhaDeRecurso>  $linhas
     * @return array<int, ItemCalculado>
     */
    private function calcularMaoDeObra(AtividadeAgricola $atividade, Propriedade $propriedade, array $linhas): array
    {
        $valoresHistoricos = $atividade->funcionarios()->pluck('atividade_funcionario.custo_hora', 'funcionarios.id');
        $valoresAtuais = $propriedade->funcionarios()->pluck('custo_hora', 'id');

        return $this->calcularItens($linhas, $valoresHistoricos, $valoresAtuais);
    }

    /**
     * Calcula cada linha usando o valor histórico (se o item já pertencia à
     * atividade) ou o valor atual do cadastro (se o item é novo).
     *
     * @param  list<LinhaDeRecurso>  $linhas
     * @param  Collection<array-key, mixed>  $valoresHistoricos  [id do recurso => valor gravado na atividade]
     * @param  Collection<array-key, mixed>  $valoresAtuais  [id do recurso => valor atual do cadastro]
     * @return array<int, ItemCalculado>
     */
    private function calcularItens(array $linhas, Collection $valoresHistoricos, Collection $valoresAtuais): array
    {
        $itens = [];

        foreach ($linhas as $linha) {
            $valor = $valoresHistoricos->get($linha['id'])
                ?? $valoresAtuais->get($linha['id'])
                ?? throw new InvalidArgumentException("O recurso #{$linha['id']} não pertence à propriedade da atividade.");

            $valor = floatval($valor);

            $itens[$linha['id']] = [
                'quantidade' => $linha['quantidade'],
                'valor' => $valor,
                'custo_total' => $this->calcularCusto($linha['quantidade'], $valor),
            ];
        }

        return $itens;
    }

    /**
     * Soma dos custos totais dos itens.
     *
     * @param  array<int, ItemCalculado>  $itens
     */
    private function somar(array $itens): float
    {
        return round(array_sum(array_column($itens, 'custo_total')), 2);
    }
}
