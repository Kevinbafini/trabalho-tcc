<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Number;

/**
 * Prepara os dados dos gráficos a partir dos indicadores de custo.
 *
 * - Telas: rótulos e valores consumidos pelo Chart.js (resources/js/graficos.js).
 * - PDF: o DomPDF não executa JavaScript, então os gráficos do relatório são
 *   desenhados em HTML/CSS; aqui são calculadas as larguras e alturas das barras.
 *
 * As três cores dos grupos de custo seguem sempre a mesma ordem
 * (insumos, maquinário, mão de obra) em todos os gráficos.
 *
 * @phpstan-import-type ResumoDeCustos from IndicadoresCustoService
 * @phpstan-import-type CustoDoTalhao from IndicadoresCustoService
 * @phpstan-import-type CustoDoMes from IndicadoresCustoService
 * @phpstan-import-type CustoDaOperacao from IndicadoresCustoService
 * @phpstan-import-type CustoDaCultura from IndicadoresCustoService
 *
 * @phpstan-type GraficoSimples array{rotulos: list<string>, valores: list<float>}
 * @phpstan-type GraficoEmpilhado array{rotulos: list<string>, series: list<array{rotulo: string, valores: list<float>}>}
 * @phpstan-type Barra array{rotulo: string, valor: float, texto: string, largura: float}
 */
class GraficosCustoService
{
    /**
     * Grupos de custo, sempre nesta ordem.
     *
     * @var list<string>
     */
    public const GRUPOS = ['Insumos', 'Maquinário', 'Mão de obra'];

    /**
     * Cores dos grupos de custo (validadas para daltonismo), na ordem de GRUPOS.
     *
     * @var list<string>
     */
    public const CORES = ['#2a78d6', '#eb6834', '#1baf7a'];

    /**
     * Composição do custo total: insumos × maquinário × mão de obra.
     *
     * @param  ResumoDeCustos  $resumo
     * @return GraficoSimples
     */
    public function composicao(array $resumo): array
    {
        return [
            'rotulos' => self::GRUPOS,
            'valores' => [$resumo['custo_insumos'], $resumo['custo_maquinas'], $resumo['custo_mao_obra']],
        ];
    }

    /**
     * Custo por hectare de cada talhão.
     *
     * @param  Collection<int, CustoDoTalhao>  $custosPorTalhao
     * @return GraficoSimples
     */
    public function custoPorHectarePorTalhao(Collection $custosPorTalhao, bool $mostrarPropriedade = false): array
    {
        return [
            'rotulos' => $this->rotulosDosTalhoes($custosPorTalhao, $mostrarPropriedade),
            'valores' => array_values($custosPorTalhao->map(fn (array $custo): float => $custo['custo_por_hectare'] ?? 0)->all()),
        ];
    }

    /**
     * Custo total de cada talhão, dividido nos três grupos de custo.
     *
     * @param  Collection<int, CustoDoTalhao>  $custosPorTalhao
     * @return GraficoEmpilhado
     */
    public function custoTotalPorTalhao(Collection $custosPorTalhao, bool $mostrarPropriedade = false): array
    {
        return [
            'rotulos' => $this->rotulosDosTalhoes($custosPorTalhao, $mostrarPropriedade),
            'series' => $this->seriesDosGrupos($custosPorTalhao->all()),
        ];
    }

    /**
     * Custo por hectare de cada cultura.
     *
     * @param  list<CustoDaCultura>  $culturas
     * @return GraficoSimples
     */
    public function custoPorHectarePorCultura(array $culturas): array
    {
        return [
            'rotulos' => array_column($culturas, 'cultura'),
            'valores' => array_map(fn (array $cultura): float => $cultura['custo_por_hectare'] ?? 0, $culturas),
        ];
    }

    /**
     * Evolução mensal dos custos, dividida nos três grupos.
     *
     * @param  list<CustoDoMes>  $meses
     * @return GraficoEmpilhado
     */
    public function custosPorMes(array $meses): array
    {
        return [
            'rotulos' => array_column($meses, 'rotulo'),
            'series' => $this->seriesDosGrupos($meses),
        ];
    }

    /**
     * Custo total de cada tipo de operação.
     *
     * @param  list<CustoDaOperacao>  $operacoes
     * @return GraficoSimples
     */
    public function custosPorOperacao(array $operacoes): array
    {
        return [
            'rotulos' => array_map(fn (array $operacao): string => $operacao['tipo']->rotulo(), $operacoes),
            'valores' => array_column($operacoes, 'custo_total'),
        ];
    }

    /**
     * Barras horizontais para o PDF: cada valor recebe a largura proporcional
     * ao maior valor da lista (o maior ocupa 100%).
     *
     * @param  GraficoSimples  $grafico
     * @return list<Barra>
     */
    public function barrasParaPdf(array $grafico): array
    {
        $maximo = $grafico['valores'] === [] ? 0 : max($grafico['valores']);

        return array_map(fn (string $rotulo, float $valor): array => [
            'rotulo' => $rotulo,
            'valor' => $valor,
            'texto' => $this->moeda($valor),
            'largura' => $this->proporcao($valor, $maximo),
        ], $grafico['rotulos'], $grafico['valores']);
    }

    /**
     * Colunas empilhadas para o PDF: altura (em pixels) de cada grupo em cada
     * coluna, proporcional ao maior total entre as colunas.
     *
     * @param  GraficoEmpilhado  $grafico
     * @return list<array{rotulo: string, total: string, alturas: list<int>}>
     */
    public function colunasParaPdf(array $grafico, int $alturaMaxima = 110): array
    {
        $totais = [];

        foreach ($grafico['rotulos'] as $indice => $rotulo) {
            $totais[$indice] = array_sum(array_map(fn (array $serie): float => $serie['valores'][$indice], $grafico['series']));
        }

        $maximo = $totais === [] ? 0 : max($totais);

        return array_map(fn (int $indice, string $rotulo): array => [
            'rotulo' => $rotulo,
            'total' => $this->valorAbreviado($totais[$indice]),
            'alturas' => array_map(
                fn (array $serie): int => (int) round($this->proporcao($serie['valores'][$indice], $maximo) / 100 * $alturaMaxima),
                $grafico['series'],
            ),
        ], array_keys($grafico['rotulos']), $grafico['rotulos']);
    }

    /**
     * Participação percentual de cada grupo no custo total (para o PDF).
     *
     * @param  ResumoDeCustos  $resumo
     * @return list<array{rotulo: string, cor: string, valor: string, percentual: float}>
     */
    public function fatiasParaPdf(array $resumo): array
    {
        $composicao = $this->composicao($resumo);

        return array_map(fn (string $rotulo, float $valor, string $cor): array => [
            'rotulo' => $rotulo,
            'cor' => $cor,
            'valor' => $this->moeda($valor),
            'percentual' => $this->proporcao($valor, $resumo['custo_total']),
        ], $composicao['rotulos'], $composicao['valores'], self::CORES);
    }

    /**
     * Valor em relação a um total, em percentual (0 a 100).
     */
    public function proporcao(float $valor, float $total): float
    {
        return $total > 0 ? round($valor / $total * 100, 1) : 0;
    }

    /**
     * Valor monetário abreviado para rótulos curtos (ex.: R$ 12,3 mil).
     */
    public function valorAbreviado(float $valor): string
    {
        return $valor >= 1000
            ? 'R$ '.Number::format($valor / 1000, precision: 1).' mil'
            : $this->moeda($valor, casasDecimais: 0);
    }

    /**
     * Valor em reais (ex.: R$ 1.234,56).
     */
    private function moeda(float $valor, int $casasDecimais = 2): string
    {
        return Number::currency($valor, precision: $casasDecimais)
            ?: 'R$ '.number_format($valor, $casasDecimais, ',', '.');
    }

    /**
     * Nome do talhão nos gráficos; com várias propriedades, inclui o nome da propriedade.
     *
     * @param  Collection<int, CustoDoTalhao>  $custosPorTalhao
     * @return list<string>
     */
    private function rotulosDosTalhoes(Collection $custosPorTalhao, bool $mostrarPropriedade): array
    {
        return array_values($custosPorTalhao
            ->map(fn (array $custo): string => $mostrarPropriedade
                ? $custo['talhao']->nome.' ('.$custo['talhao']->propriedade->nome.')'
                : $custo['talhao']->nome)
            ->all());
    }

    /**
     * Séries dos três grupos de custo a partir de itens com custo_insumos,
     * custo_maquinas e custo_mao_obra.
     *
     * @param  array<int, array{custo_insumos: float, custo_maquinas: float, custo_mao_obra: float}>  $itens
     * @return list<array{rotulo: string, valores: list<float>}>
     */
    private function seriesDosGrupos(array $itens): array
    {
        return [
            ['rotulo' => self::GRUPOS[0], 'valores' => array_column($itens, 'custo_insumos')],
            ['rotulo' => self::GRUPOS[1], 'valores' => array_column($itens, 'custo_maquinas')],
            ['rotulo' => self::GRUPOS[2], 'valores' => array_column($itens, 'custo_mao_obra')],
        ];
    }
}
