<?php

namespace App\Http\Resources;

use App\Models\AtividadeAgricola;
use App\Models\Funcionario;
use App\Models\Implemento;
use App\Models\Insumo;
use App\Models\Maquina;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

/**
 * Atividade agrícola com os custos gravados no lançamento.
 *
 * Os itens (insumos, máquinas, funcionários e implementos) aparecem nos
 * detalhes da atividade, com os valores históricos das tabelas de
 * relacionamento: alterar o cadastro de um insumo depois não muda o custo
 * que aparece aqui. Os campos de cada item usam os mesmos nomes do envio
 * (insumo_id, quantidade, horas...), para facilitar a alteração com PUT.
 *
 * @mixin AtividadeAgricola
 */
class AtividadeAgricolaResource extends JsonResource
{
    /**
     * A atividade tem um campo chamado "data" (a data da operação). Sem isto, o
     * Laravel acharia que a resposta já está dentro do envelope "data" e não a
     * envolveria, fugindo do formato {"data": {...}} das demais respostas.
     */
    public static bool $forceWrapping = true;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'propriedade_id' => $this->propriedade_id,
            'talhao_id' => $this->talhao_id,
            'propriedade' => $this->whenLoaded('propriedade', fn (): array => [
                'id' => $this->propriedade->id,
                'nome' => $this->propriedade->nome,
            ]),
            'talhao' => $this->whenLoaded('talhao', fn (): array => [
                'id' => $this->talhao->id,
                'nome' => $this->talhao->nome,
                'cultura' => $this->talhao->cultura,
                'area' => $this->talhao->area,
            ]),
            'data' => $this->data->toDateString(),
            'tipo' => $this->tipo->value,
            'tipo_rotulo' => $this->tipo->rotulo(),
            'safra' => $this->safra,
            'descricao' => $this->descricao,
            'custo_insumos' => $this->custo_insumos,
            'custo_maquinas' => $this->custo_maquinas,
            'custo_mao_obra' => $this->custo_mao_obra,
            'custo_total' => $this->custo_total,
            'insumos' => $this->whenLoaded('insumos', fn (): array => $this->insumos->map(fn (Insumo $insumo): array => [
                'insumo_id' => $insumo->id,
                'nome' => $insumo->nome,
                'unidade' => $insumo->unidade->value,
                'quantidade' => $this->valorGravado($insumo, 'quantidade'),
                'custo_unitario' => $this->valorGravado($insumo, 'custo_unitario'),
                'custo_total' => $this->valorGravado($insumo, 'custo_total'),
            ])->all()),
            'maquinas' => $this->whenLoaded('maquinas', fn (): array => $this->maquinas->map(fn (Maquina $maquina): array => [
                'maquina_id' => $maquina->id,
                'nome' => $maquina->nome,
                'horas' => $this->valorGravado($maquina, 'horas'),
                'custo_hora' => $this->valorGravado($maquina, 'custo_hora'),
                'custo_total' => $this->valorGravado($maquina, 'custo_total'),
            ])->all()),
            'funcionarios' => $this->whenLoaded('funcionarios', fn (): array => $this->funcionarios->map(fn (Funcionario $funcionario): array => [
                'funcionario_id' => $funcionario->id,
                'nome' => $funcionario->nome,
                'funcao' => $funcionario->funcao,
                'horas' => $this->valorGravado($funcionario, 'horas'),
                'custo_hora' => $this->valorGravado($funcionario, 'custo_hora'),
                'custo_total' => $this->valorGravado($funcionario, 'custo_total'),
            ])->all()),
            'implementos' => $this->whenLoaded('implementos', fn (): array => $this->implementos->map(fn (Implemento $implemento): array => [
                'implemento_id' => $implemento->id,
                'nome' => $implemento->nome,
            ])->all()),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Valor gravado no lançamento, na tabela de relacionamento (pivô) entre a
     * atividade e o recurso: quantidade, horas, custo unitário/hora ou custo total.
     */
    private function valorGravado(Model $recurso, string $coluna): float
    {
        $pivo = $recurso->getRelation('pivot');

        if (! $pivo instanceof Pivot) {
            throw new LogicException('O recurso precisa ser carregado pela relação com a atividade.');
        }

        return floatval($pivo->getAttribute($coluna));
    }
}
