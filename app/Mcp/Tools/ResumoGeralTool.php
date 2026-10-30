<?php

namespace App\Mcp\Tools;

use App\Models\AtividadeAgricola;
use App\Models\Propriedade;
use App\Models\User;
use App\Services\IndicadoresCustoService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
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
#[Name('resumo-geral')]
#[Title('Resumo geral das propriedades')]
#[Description('Visão geral do usuário: propriedades (com IDs), indicadores de custo, safras e últimas atividades. Use como primeiro passo para entender os dados ou obter IDs de propriedades.')]
class ResumoGeralTool extends Ferramenta
{
    public function __construct(private IndicadoresCustoService $indicadores) {}

    protected function executar(Request $request, User $usuario): Response
    {
        $validado = $request->validate(['propriedade_id' => ['nullable', 'integer']]);
        $propriedadeId = isset($validado['propriedade_id']) ? (int) $validado['propriedade_id'] : null;

        $propriedades = Propriedade::query()->doUsuario($usuario)->withCount('talhoes')->orderBy('nome')->get();

        if ($propriedadeId !== null && ! $propriedades->contains('id', $propriedadeId)) {
            return Response::error('Propriedade não encontrada entre as do usuário. Use os IDs listados em resumo-geral.');
        }

        $filtros = ['propriedade_id' => $propriedadeId];
        $resumo = $this->indicadores->resumo($usuario, $filtros);

        return Response::json([
            'data_de_hoje' => now()->format('d/m/Y'),
            'propriedades' => $propriedades->map(fn (Propriedade $propriedade): array => [
                'id' => $propriedade->id,
                'nome' => $propriedade->nome,
                'municipio' => $propriedade->municipio.'/'.$propriedade->uf,
                'area_total' => $this->hectares($propriedade->area_total),
                'talhoes' => $propriedade->talhoes_count,
            ])->all(),
            'indicadores' => [
                'atividades_registradas' => $resumo['quantidade_atividades'],
                'custo_insumos' => $this->moeda($resumo['custo_insumos']),
                'custo_maquinas' => $this->moeda($resumo['custo_maquinas']),
                'custo_mao_de_obra' => $this->moeda($resumo['custo_mao_obra']),
                'custo_total' => $this->moeda($resumo['custo_total']),
                'area_dos_talhoes_com_atividades' => $this->hectares($resumo['area']),
                'custo_medio_por_hectare' => $resumo['custo_por_hectare'] === null ? null : $this->moeda($resumo['custo_por_hectare']),
            ],
            'safras' => $this->indicadores->safras($usuario),
            'ultimas_atividades' => $this->indicadores->ultimasAtividades($usuario, $filtros, 5)
                ->map(fn (AtividadeAgricola $atividade): array => [
                    'id' => $atividade->id,
                    'data' => $atividade->data->format('d/m/Y'),
                    'operacao' => $atividade->tipo->rotulo(),
                    'talhao' => $atividade->talhao->nome,
                    'custo_total' => $this->moeda($atividade->custo_total),
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
            'propriedade_id' => $schema->integer()->min(1)->description('Limita os indicadores a uma propriedade (opcional).'),
        ];
    }
}
