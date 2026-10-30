<?php

namespace App\Mcp\Tools;

use App\Http\Requests\FiltroCustosRequest;
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

/**
 * Os mesmos números das telas Custos por talhão e Relatório de custos,
 * calculados pelo IndicadoresCustoService com os mesmos filtros.
 *
 * @phpstan-import-type CustoDoTalhao from IndicadoresCustoService
 * @phpstan-import-type CustoDoMes from IndicadoresCustoService
 * @phpstan-import-type CustoDaOperacao from IndicadoresCustoService
 * @phpstan-import-type CustoDaCultura from IndicadoresCustoService
 */
#[IsReadOnly]
#[IsIdempotent]
#[IsOpenWorld(false)]
#[Name('consultar-custos')]
#[Title('Consultar custos')]
#[Description('Custos das atividades com filtros opcionais (propriedade, talhão, safra, período): totais por grupo (insumos, maquinário, mão de obra), custo por hectare, custo por talhão, por tipo de operação, por mês e por cultura. Use para qualquer pergunta sobre gastos ou custos.')]
class ConsultarCustosTool extends Ferramenta
{
    public function __construct(private IndicadoresCustoService $indicadores) {}

    protected function executar(Request $request, User $usuario): Response
    {
        $filtros = $this->validarComFormulario(FiltroCustosRequest::class, $request->all(), $usuario, 'GET')->filtros();

        $resumo = $this->indicadores->resumo($usuario, $filtros);
        $custosPorTalhao = $this->indicadores->custosPorTalhao($usuario, $filtros, somenteComAtividades: true);

        return Response::json([
            'filtros_aplicados' => array_filter($filtros),
            'resumo' => [
                'atividades' => $resumo['quantidade_atividades'],
                'custo_insumos' => $this->moeda($resumo['custo_insumos']),
                'custo_maquinas' => $this->moeda($resumo['custo_maquinas']),
                'custo_mao_de_obra' => $this->moeda($resumo['custo_mao_obra']),
                'custo_total' => $this->moeda($resumo['custo_total']),
                'area_dos_talhoes' => $this->hectares($resumo['area']),
                'custo_por_hectare' => $this->moedaOuNulo($resumo['custo_por_hectare']),
            ],
            'por_talhao' => $custosPorTalhao
                ->sortByDesc('custo_total')
                ->values()
                ->map(fn (array $custo): array => [
                    'talhao_id' => $custo['talhao']->id,
                    'talhao' => $custo['talhao']->nome,
                    'propriedade' => $custo['talhao']->propriedade->nome,
                    'cultura' => $custo['talhao']->cultura,
                    'area' => $this->hectares($custo['talhao']->area),
                    'atividades' => $custo['quantidade_atividades'],
                    'custo_total' => $this->moeda($custo['custo_total']),
                    'custo_por_hectare' => $this->moedaOuNulo($custo['custo_por_hectare']),
                ])
                ->all(),
            'por_operacao' => array_map(fn (array $operacao): array => [
                'operacao' => $operacao['tipo']->rotulo(),
                'atividades' => $operacao['quantidade_atividades'],
                'custo_total' => $this->moeda($operacao['custo_total']),
            ], $this->indicadores->custosPorOperacao($usuario, $filtros)),
            'por_mes' => array_map(fn (array $mes): array => [
                'mes' => $mes['rotulo'],
                'custo_total' => $this->moeda($mes['custo_total']),
            ], $this->indicadores->custosPorMes($usuario, $filtros)),
            'por_cultura' => array_map(fn (array $cultura): array => [
                'cultura' => $cultura['cultura'],
                'area' => $this->hectares($cultura['area']),
                'custo_total' => $this->moeda($cultura['custo_total']),
                'custo_por_hectare' => $this->moedaOuNulo($cultura['custo_por_hectare']),
            ], $this->indicadores->custosPorCultura($custosPorTalhao)),
        ]);
    }

    private function moedaOuNulo(?float $valor): ?string
    {
        return $valor === null ? null : $this->moeda($valor);
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
            'safra' => $schema->string()->description('Safra no formato ano/ano, ex.: 2025/2026 (opcional).'),
            'data_inicio' => $schema->string()->format('date')->description('Início do período, AAAA-MM-DD (opcional).'),
            'data_fim' => $schema->string()->format('date')->description('Fim do período, AAAA-MM-DD (opcional).'),
        ];
    }
}
