<?php

namespace App\Http\Resources;

use App\Http\Requests\FiltroCustosRequest;
use App\Services\IndicadoresCustoService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * Relatório de custos: os mesmos números das telas Custos por talhão e
 * Relatórios, calculados pelo IndicadoresCustoService com os mesmos filtros.
 *
 * Os valores saem como números (ex.: 1335.5), não como texto formatado em
 * reais: quem consome a API decide como exibir.
 *
 * @phpstan-import-type FiltrosValidados from FiltroCustosRequest
 * @phpstan-import-type ResumoDeCustos from IndicadoresCustoService
 * @phpstan-import-type CustoDoTalhao from IndicadoresCustoService
 * @phpstan-import-type CustoDaOperacao from IndicadoresCustoService
 * @phpstan-import-type CustoDoMes from IndicadoresCustoService
 * @phpstan-import-type CustoDaCultura from IndicadoresCustoService
 *
 * @phpstan-type Relatorio array{
 *     filtros: FiltrosValidados,
 *     resumo: ResumoDeCustos,
 *     por_talhao: Collection<int, CustoDoTalhao>,
 *     por_operacao: list<CustoDaOperacao>,
 *     por_mes: list<CustoDoMes>,
 *     por_cultura: list<CustoDaCultura>,
 * }
 */
class RelatorioDeCustosResource extends JsonResource
{
    /**
     * @param  Relatorio  $relatorio
     */
    public function __construct(private readonly array $relatorio)
    {
        parent::__construct($relatorio);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'filtros' => $this->relatorio['filtros'],
            'resumo' => $this->relatorio['resumo'],
            'por_talhao' => $this->relatorio['por_talhao']
                ->map(fn (array $custo): array => [
                    'talhao_id' => $custo['talhao']->id,
                    'talhao' => $custo['talhao']->nome,
                    'propriedade_id' => $custo['talhao']->propriedade_id,
                    'propriedade' => $custo['talhao']->propriedade->nome,
                    'cultura' => $custo['talhao']->cultura,
                    'area' => $custo['talhao']->area,
                    'quantidade_atividades' => $custo['quantidade_atividades'],
                    'custo_insumos' => $custo['custo_insumos'],
                    'custo_maquinas' => $custo['custo_maquinas'],
                    'custo_mao_obra' => $custo['custo_mao_obra'],
                    'custo_total' => $custo['custo_total'],
                    'custo_por_hectare' => $custo['custo_por_hectare'],
                ])
                ->values()
                ->all(),
            'por_operacao' => array_map(fn (array $operacao): array => [
                'tipo' => $operacao['tipo']->value,
                'tipo_rotulo' => $operacao['tipo']->rotulo(),
                'quantidade_atividades' => $operacao['quantidade_atividades'],
                'custo_total' => $operacao['custo_total'],
            ], $this->relatorio['por_operacao']),
            'por_mes' => $this->relatorio['por_mes'],
            'por_cultura' => $this->relatorio['por_cultura'],
        ];
    }
}
