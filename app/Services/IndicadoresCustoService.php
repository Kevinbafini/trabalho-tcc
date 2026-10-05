<?php

namespace App\Services;

use App\Enums\TipoOperacao;
use App\Models\AtividadeAgricola;
use App\Models\Propriedade;
use App\Models\Talhao;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * Indicadores de custo usados no dashboard, na tela de custos por talhão
 * e nos relatórios.
 *
 * Como cada atividade já guarda seus custos (snapshot do lançamento), os
 * indicadores são somas dessas colunas. Todas as consultas partem dos dados
 * do usuário informado, garantindo o isolamento entre usuários.
 *
 * Custo por hectare = custo total ÷ área (ha) dos talhões que receberam as atividades.
 *
 * @phpstan-type Filtros array{
 *     propriedade_id?: int|null,
 *     talhao_id?: int|null,
 *     safra?: string|null,
 *     data_inicio?: string|null,
 *     data_fim?: string|null,
 * }
 * @phpstan-type ResumoDeCustos array{
 *     quantidade_atividades: int,
 *     custo_insumos: float,
 *     custo_maquinas: float,
 *     custo_mao_obra: float,
 *     custo_total: float,
 *     area: float,
 *     custo_por_hectare: float|null,
 * }
 * @phpstan-type CustoDoMes array{
 *     mes: string,
 *     rotulo: string,
 *     custo_insumos: float,
 *     custo_maquinas: float,
 *     custo_mao_obra: float,
 *     custo_total: float,
 * }
 * @phpstan-type CustoDaOperacao array{tipo: TipoOperacao, quantidade_atividades: int, custo_total: float}
 * @phpstan-type CustoDaCultura array{cultura: string, area: float, custo_total: float, custo_por_hectare: float|null}
 * @phpstan-type CustoDoTalhao array{
 *     talhao: Talhao,
 *     quantidade_atividades: int,
 *     custo_insumos: float,
 *     custo_maquinas: float,
 *     custo_mao_obra: float,
 *     custo_total: float,
 *     custo_por_hectare: float|null,
 * }
 */
class IndicadoresCustoService
{
    /**
     * Atividades do usuário com os filtros aplicados.
     *
     * @param  Filtros  $filtros
     * @return Builder<AtividadeAgricola>
     */
    public function atividades(User $usuario, array $filtros = []): Builder
    {
        $consulta = AtividadeAgricola::query()
            ->doUsuario($usuario)
            ->when($filtros['propriedade_id'] ?? null, fn (Builder $query, int $id) => $query->where('propriedade_id', $id))
            ->when($filtros['talhao_id'] ?? null, fn (Builder $query, int $id) => $query->where('talhao_id', $id));

        $this->filtrarPeriodo($consulta, $filtros);

        return $consulta;
    }

    /**
     * Totais de custo das atividades filtradas e o custo por hectare.
     *
     * @param  Filtros  $filtros
     * @return ResumoDeCustos
     */
    public function resumo(User $usuario, array $filtros = []): array
    {
        $custoInsumos = round(floatval($this->atividades($usuario, $filtros)->sum('custo_insumos')), 2);
        $custoMaquinas = round(floatval($this->atividades($usuario, $filtros)->sum('custo_maquinas')), 2);
        $custoMaoDeObra = round(floatval($this->atividades($usuario, $filtros)->sum('custo_mao_obra')), 2);
        $custoTotal = round(floatval($this->atividades($usuario, $filtros)->sum('custo_total')), 2);

        // Área considerada: talhões que receberam as atividades filtradas.
        $area = round(floatval(Talhao::query()
            ->whereIn('id', $this->atividades($usuario, $filtros)->select('talhao_id'))
            ->sum('area')), 2);

        return [
            'quantidade_atividades' => $this->atividades($usuario, $filtros)->count(),
            'custo_insumos' => $custoInsumos,
            'custo_maquinas' => $custoMaquinas,
            'custo_mao_obra' => $custoMaoDeObra,
            'custo_total' => $custoTotal,
            'area' => $area,
            'custo_por_hectare' => $this->custoPorHectare($custoTotal, $area),
        ];
    }

    /**
     * Custos acumulados de cada talhão. Por padrão inclui os talhões ainda sem
     * atividades; com $somenteComAtividades, só os que tiveram atividades no filtro.
     *
     * @param  Filtros  $filtros
     * @return Collection<int, CustoDoTalhao>
     */
    public function custosPorTalhao(User $usuario, array $filtros = [], bool $somenteComAtividades = false): Collection
    {
        $filtrarAtividades = fn (Builder $consulta) => $this->filtrarPeriodo($consulta, $filtros);

        return Talhao::query()
            ->doUsuario($usuario)
            ->with('propriedade')
            ->when($filtros['propriedade_id'] ?? null, fn (Builder $query, int $id) => $query->where('propriedade_id', $id))
            ->when($filtros['talhao_id'] ?? null, fn (Builder $query, int $id) => $query->whereKey($id))
            ->when($somenteComAtividades, fn (Builder $query) => $query->whereHas('atividades', $filtrarAtividades))
            ->withCount(['atividades as quantidade_atividades' => $filtrarAtividades])
            ->withSum(['atividades as soma_insumos' => $filtrarAtividades], 'custo_insumos')
            ->withSum(['atividades as soma_maquinas' => $filtrarAtividades], 'custo_maquinas')
            ->withSum(['atividades as soma_mao_obra' => $filtrarAtividades], 'custo_mao_obra')
            ->withSum(['atividades as soma_total' => $filtrarAtividades], 'custo_total')
            ->orderBy('nome')
            ->get()
            ->toBase()
            ->map(function (Talhao $talhao): array {
                $custoTotal = round(floatval($talhao->getAttribute('soma_total')), 2);

                return [
                    'talhao' => $talhao,
                    'quantidade_atividades' => intval($talhao->getAttribute('quantidade_atividades')),
                    'custo_insumos' => round(floatval($talhao->getAttribute('soma_insumos')), 2),
                    'custo_maquinas' => round(floatval($talhao->getAttribute('soma_maquinas')), 2),
                    'custo_mao_obra' => round(floatval($talhao->getAttribute('soma_mao_obra')), 2),
                    'custo_total' => $custoTotal,
                    'custo_por_hectare' => $this->custoPorHectare($custoTotal, $talhao->area),
                ];
            });
    }

    /**
     * Custos agrupados por mês, do primeiro ao último mês com atividades
     * (meses sem atividades aparecem com custo zero).
     *
     * @param  Filtros  $filtros
     * @return list<CustoDoMes>
     */
    public function custosPorMes(User $usuario, array $filtros = []): array
    {
        $atividades = $this->atividades($usuario, $filtros)
            ->orderBy('data')
            ->get(['data', 'custo_insumos', 'custo_maquinas', 'custo_mao_obra', 'custo_total']);

        if ($atividades->isEmpty()) {
            return [];
        }

        $porMes = $atividades->groupBy(fn (AtividadeAgricola $atividade): string => $atividade->data->format('Y-m'));
        $mes = CarbonImmutable::parse($atividades->firstOrFail()->data)->startOfMonth();
        $ultimoMes = CarbonImmutable::parse($atividades->last()->data)->startOfMonth();
        $meses = [];

        while ($mes <= $ultimoMes) {
            $doMes = $porMes->get($mes->format('Y-m'), collect());

            $meses[] = [
                'mes' => $mes->format('Y-m'),
                'rotulo' => ucfirst($mes->translatedFormat('M/y')),
                'custo_insumos' => round(floatval($doMes->sum('custo_insumos')), 2),
                'custo_maquinas' => round(floatval($doMes->sum('custo_maquinas')), 2),
                'custo_mao_obra' => round(floatval($doMes->sum('custo_mao_obra')), 2),
                'custo_total' => round(floatval($doMes->sum('custo_total')), 2),
            ];

            $mes = $mes->addMonth();
        }

        return $meses;
    }

    /**
     * Custo total por tipo de operação, do maior para o menor.
     *
     * @param  Filtros  $filtros
     * @return list<CustoDaOperacao>
     */
    public function custosPorOperacao(User $usuario, array $filtros = []): array
    {
        return array_values($this->atividades($usuario, $filtros)
            ->get(['tipo', 'custo_total'])
            ->groupBy(fn (AtividadeAgricola $atividade): string => $atividade->tipo->value)
            ->map(fn ($grupo): array => [
                'tipo' => $grupo->firstOrFail()->tipo,
                'quantidade_atividades' => $grupo->count(),
                'custo_total' => round(floatval($grupo->sum('custo_total')), 2),
            ])
            ->sortByDesc('custo_total')
            ->all());
    }

    /**
     * Custo por hectare de cada cultura: soma dos custos dos talhões da
     * cultura ÷ soma das áreas desses talhões.
     *
     * @param  Collection<int, CustoDoTalhao>  $custosPorTalhao
     * @return list<CustoDaCultura>
     */
    public function custosPorCultura(Collection $custosPorTalhao): array
    {
        return array_values($custosPorTalhao
            ->groupBy(fn (array $custo): string => $custo['talhao']->cultura)
            ->map(function (Collection $grupo, string $cultura): array {
                $area = round(floatval($grupo->sum(fn (array $custo): float => $custo['talhao']->area)), 2);
                $custoTotal = round(floatval($grupo->sum('custo_total')), 2);

                return [
                    'cultura' => $cultura,
                    'area' => $area,
                    'custo_total' => $custoTotal,
                    'custo_por_hectare' => $this->custoPorHectare($custoTotal, $area),
                ];
            })
            ->sortByDesc('custo_por_hectare')
            ->all());
    }

    /**
     * Atividades mais recentes, para o dashboard.
     *
     * @param  Filtros  $filtros
     * @return EloquentCollection<int, AtividadeAgricola>
     */
    public function ultimasAtividades(User $usuario, array $filtros = [], int $limite = 6): EloquentCollection
    {
        return $this->atividades($usuario, $filtros)
            ->with('talhao')
            ->orderByDesc('data')
            ->orderByDesc('id')
            ->limit($limite)
            ->get();
    }

    /**
     * Área total das propriedades do usuário (ou de uma propriedade).
     */
    public function areaTotal(User $usuario, ?int $propriedadeId = null): float
    {
        return round(floatval(Propriedade::query()
            ->doUsuario($usuario)
            ->when($propriedadeId, fn (Builder $query, int $id) => $query->whereKey($id))
            ->sum('area_total')), 2);
    }

    /**
     * Quantidade de talhões do usuário (ou de uma propriedade).
     */
    public function quantidadeDeTalhoes(User $usuario, ?int $propriedadeId = null): int
    {
        return Talhao::query()
            ->doUsuario($usuario)
            ->when($propriedadeId, fn (Builder $query, int $id) => $query->where('propriedade_id', $id))
            ->count();
    }

    /**
     * Safras que possuem atividades, para os filtros.
     *
     * @return list<string>
     */
    public function safras(User $usuario): array
    {
        return array_values(AtividadeAgricola::query()
            ->doUsuario($usuario)
            ->distinct()
            ->orderByDesc('safra')
            ->pluck('safra')
            ->map(fn (mixed $safra): string => strval($safra))
            ->all());
    }

    /**
     * Custo por hectare = custo total ÷ área. Retorna nulo quando não há área.
     */
    public function custoPorHectare(float $custoTotal, float $area): ?float
    {
        return $area > 0 ? round($custoTotal / $area, 2) : null;
    }

    /**
     * Aplica os filtros de safra e período a uma consulta de atividades.
     *
     * @param  Builder<AtividadeAgricola>  $consulta
     * @param  Filtros  $filtros
     */
    private function filtrarPeriodo(Builder $consulta, array $filtros): void
    {
        $consulta
            ->when($filtros['safra'] ?? null, fn (Builder $query, string $safra) => $query->where('safra', $safra))
            ->when($filtros['data_inicio'] ?? null, fn (Builder $query, string $data) => $query->whereDate('data', '>=', $data))
            ->when($filtros['data_fim'] ?? null, fn (Builder $query, string $data) => $query->whereDate('data', '<=', $data));
    }
}
