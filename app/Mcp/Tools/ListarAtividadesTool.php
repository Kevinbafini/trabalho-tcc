<?php

namespace App\Mcp\Tools;

use App\Enums\TipoOperacao;
use App\Http\Requests\FiltroCustosRequest;
use App\Models\AtividadeAgricola;
use App\Models\User;
use App\Services\IndicadoresCustoService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[IsIdempotent]
#[IsOpenWorld(false)]
#[Name('listar-atividades')]
#[Title('Listar atividades')]
#[Description('Lista as atividades agrícolas do usuário, das mais recentes para as mais antigas, com custos e link. Filtros opcionais: propriedade, talhão, safra, período e tipo de operação.')]
class ListarAtividadesTool extends Ferramenta
{
    public function __construct(private IndicadoresCustoService $indicadores) {}

    protected function executar(Request $request, User $usuario): Response
    {
        $filtros = $this->validarComFormulario(FiltroCustosRequest::class, $request->all(), $usuario, 'GET')->filtros();

        $validado = $request->validate([
            'tipo' => ['nullable', Rule::enum(TipoOperacao::class)],
            'limite' => ['nullable', 'integer', 'between:1,30'],
        ]);

        $consulta = $this->indicadores->atividades($usuario, $filtros)
            ->when($validado['tipo'] ?? null, fn (Builder $query, string $tipo) => $query->where('tipo', $tipo));

        $total = (clone $consulta)->count();

        $atividades = $consulta
            ->with(['talhao', 'propriedade'])
            ->orderByDesc('data')
            ->orderByDesc('id')
            ->limit((int) ($validado['limite'] ?? 10))
            ->get();

        return Response::json([
            'total_encontrado' => $total,
            'exibindo' => $atividades->count(),
            'atividades' => $atividades->map(fn (AtividadeAgricola $atividade): array => [
                'id' => $atividade->id,
                'data' => $atividade->data->format('d/m/Y'),
                'operacao' => $atividade->tipo->rotulo(),
                'propriedade' => $atividade->propriedade->nome,
                'talhao' => $atividade->talhao->nome,
                'safra' => $atividade->safra,
                'descricao' => $atividade->descricao,
                'custo_insumos' => $this->moeda($atividade->custo_insumos),
                'custo_maquinas' => $this->moeda($atividade->custo_maquinas),
                'custo_mao_de_obra' => $this->moeda($atividade->custo_mao_obra),
                'custo_total' => $this->moeda($atividade->custo_total),
                'link' => route('atividades.show', $atividade),
            ])->all(),
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
            'propriedade_id' => $schema->integer()->min(1)->description('ID da propriedade (opcional).'),
            'talhao_id' => $schema->integer()->min(1)->description('ID do talhão (opcional).'),
            'safra' => $schema->string()->description('Safra no formato ano/ano (opcional).'),
            'data_inicio' => $schema->string()->format('date')->description('Início do período, AAAA-MM-DD (opcional).'),
            'data_fim' => $schema->string()->format('date')->description('Fim do período, AAAA-MM-DD (opcional).'),
            'tipo' => $schema->string()->enum(TipoOperacao::class)->description('Tipo de operação (opcional).'),
            'limite' => $schema->integer()->min(1)->max(30)->description('Quantas atividades devolver (padrão: 10, máximo: 30).'),
        ];
    }
}
