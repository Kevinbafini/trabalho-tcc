<?php

namespace App\Mcp\Tools;

use App\Enums\TipoOperacao;
use App\Http\Requests\AtividadeAgricolaRequest;
use App\Models\AtividadeAgricola;
use App\Models\Funcionario;
use App\Models\Insumo;
use App\Models\Maquina;
use App\Models\User;
use App\Services\CustoAtividadeService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Carbon;
use Illuminate\Support\Number;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly(false)]
#[IsDestructive(false)]
#[IsOpenWorld(false)]
#[Name('registrar-atividade')]
#[Title('Registrar atividade agrícola')]
#[Description('Registra uma operação (plantio, adubação, pulverização, colheita...) em um talhão, com os insumos, máquinas, funcionários e implementos usados. O custo é calculado pelo sistema com os preços dos cadastros. Chame primeiro com confirmado_pelo_usuario=false: a prévia traz o custo calculado item por item, sem salvar. Só grave após a confirmação explícita do usuário.')]
class RegistrarAtividadeTool extends FerramentaDeCadastro
{
    public function __construct(private CustoAtividadeService $custos) {}

    protected function formulario(): string
    {
        return AtividadeAgricolaRequest::class;
    }

    protected function dados(Request $request): array
    {
        return [
            ...$request->all(['propriedade_id', 'talhao_id', 'data', 'tipo', 'descricao']),
            'insumos' => (array) $request->get('insumos', []),
            'maquinas' => (array) $request->get('maquinas', []),
            'funcionarios' => (array) $request->get('funcionarios', []),
            'implementos' => (array) $request->get('implementos', []),
        ];
    }

    protected function previa(FormRequest $formulario, User $usuario): array
    {
        assert($formulario instanceof AtividadeAgricolaRequest);

        $propriedade = $formulario->propriedadeSelecionada();
        $dados = $formulario->dadosDaAtividade();
        $calculo = $this->custos->calcularPrevia($propriedade, $dados);

        $talhao = $propriedade->talhoes()->findOrFail($dados['talhao_id']);
        $insumos = $propriedade->insumos()->whereKey(array_keys($calculo['insumos']))->get()->keyBy('id');
        $maquinas = $propriedade->maquinas()->whereKey(array_keys($calculo['maquinas']))->get()->keyBy('id');
        $funcionarios = $propriedade->funcionarios()->whereKey(array_keys($calculo['funcionarios']))->get()->keyBy('id');

        $linhasDeInsumos = [];

        foreach ($calculo['insumos'] as $id => $item) {
            /** @var Insumo $insumo */
            $insumo = $insumos[$id];
            $unidade = $insumo->unidade->value;
            $linhasDeInsumos[] = "{$insumo->nome}: {$this->numero($item['quantidade'])} {$unidade} × {$this->moeda($item['valor'])}/{$unidade} = {$this->moeda($item['custo_total'])}";
        }

        $linhasDeMaquinas = [];

        foreach ($calculo['maquinas'] as $id => $item) {
            /** @var Maquina $maquina */
            $maquina = $maquinas[$id];
            $linhasDeMaquinas[] = "{$maquina->nome}: {$this->numero($item['quantidade'])} h × {$this->moeda($item['valor'])}/h = {$this->moeda($item['custo_total'])}";
        }

        $linhasDeMaoDeObra = [];

        foreach ($calculo['funcionarios'] as $id => $item) {
            /** @var Funcionario $funcionario */
            $funcionario = $funcionarios[$id];
            $linhasDeMaoDeObra[] = "{$funcionario->nome} ({$funcionario->funcao}): {$this->numero($item['quantidade'])} h × {$this->moeda($item['valor'])}/h = {$this->moeda($item['custo_total'])}";
        }

        return [
            'propriedade' => $propriedade->nome,
            'talhao' => "{$talhao->nome} ({$talhao->cultura}, {$this->hectares($talhao->area)})",
            'safra' => $talhao->safra,
            'data' => Carbon::parse($dados['data'])->format('d/m/Y'),
            'operacao' => $dados['tipo']->rotulo(),
            'descricao' => $dados['descricao'],
            'insumos' => $linhasDeInsumos,
            'maquinas' => $linhasDeMaquinas,
            'mao_de_obra' => $linhasDeMaoDeObra,
            'implementos_sem_custo_proprio' => $propriedade->implementos()->whereKey($dados['implementos'])->pluck('nome')->all(),
            'custo_insumos' => $this->moeda($calculo['custo_insumos']),
            'custo_maquinas' => $this->moeda($calculo['custo_maquinas']),
            'custo_mao_de_obra' => $this->moeda($calculo['custo_mao_obra']),
            'custo_total' => $this->moeda($calculo['custo_total']),
        ];
    }

    protected function salvar(FormRequest $formulario, User $usuario): Response
    {
        assert($formulario instanceof AtividadeAgricolaRequest);

        $atividade = $this->custos->salvar(
            new AtividadeAgricola,
            $formulario->propriedadeSelecionada(),
            $formulario->dadosDaAtividade(),
        );

        return Response::json([
            'salvo' => true,
            'mensagem' => 'Atividade registrada. Custo total calculado: '.$this->moeda($atividade->custo_total).'.',
            'atividade' => [
                'id' => $atividade->id,
                'data' => $atividade->data->format('d/m/Y'),
                'operacao' => $atividade->tipo->rotulo(),
                'talhao' => $atividade->talhao->nome,
                'safra' => $atividade->safra,
                'custo_total' => $this->moeda($atividade->custo_total),
            ],
            'link' => route('atividades.show', $atividade),
        ]);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'confirmado_pelo_usuario' => $this->campoDeConfirmacao($schema),
            'propriedade_id' => $schema->integer()->min(1)->description('ID da propriedade. Todos os recursos precisam ser desta propriedade.')->required(),
            'talhao_id' => $schema->integer()->min(1)->description('ID do talhão onde a operação foi feita, obtido com buscar-cadastros. Nunca invente.')->required(),
            'data' => $schema->string()->format('date')->description('Data da operação no formato AAAA-MM-DD. Não pode ser futura.')->required(),
            'tipo' => $schema->string()->enum(TipoOperacao::class)->description('Operação: preparo_solo, plantio, adubacao, pulverizacao, irrigacao, tratos_culturais, colheita ou outra.')->required(),
            'descricao' => $schema->string()->max(1000)->description('Descrição livre (opcional), com as palavras do usuário.'),
            'insumos' => $schema->array()->items($schema->object([
                'insumo_id' => $schema->integer()->min(1)->description('ID do insumo.')->required(),
                'quantidade' => $schema->number()->description('Quantidade aplicada, na unidade do insumo (kg, L, t...).')->required(),
            ]))->description('Insumos aplicados.'),
            'maquinas' => $schema->array()->items($schema->object([
                'maquina_id' => $schema->integer()->min(1)->description('ID da máquina.')->required(),
                'horas' => $schema->number()->description('Horas de uso.')->required(),
            ]))->description('Máquinas usadas.'),
            'funcionarios' => $schema->array()->items($schema->object([
                'funcionario_id' => $schema->integer()->min(1)->description('ID do funcionário.')->required(),
                'horas' => $schema->number()->description('Horas trabalhadas.')->required(),
            ]))->description('Mão de obra.'),
            'implementos' => $schema->array()->items($schema->integer()->min(1))->description('IDs dos implementos acoplados às máquinas (sem custo próprio).'),
        ];
    }

    /**
     * Quantidade sem zeros desnecessários (ex.: 2,5 e não 2,50).
     */
    private function numero(float $valor): string
    {
        return (string) Number::format($valor, maxPrecision: 3);
    }
}
