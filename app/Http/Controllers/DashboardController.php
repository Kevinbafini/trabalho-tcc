<?php

namespace App\Http\Controllers;

use App\Http\Requests\FiltroCustosRequest;
use App\Models\User;
use App\Services\IndicadoresCustoService;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Indicadores gerais, gráficos de custos e últimas atividades.
     */
    public function __invoke(FiltroCustosRequest $request, #[CurrentUser] User $usuario, IndicadoresCustoService $indicadores): View
    {
        $propriedades = $this->propriedadesDoUsuario($usuario);
        $propriedadeId = $request->filtros()['propriedade_id'];
        $filtros = ['propriedade_id' => $propriedadeId];

        $resumo = $indicadores->resumo($usuario, $filtros);
        $custosPorTalhao = $indicadores->custosPorTalhao($usuario, $filtros);

        // Com mais de uma propriedade, o nome da propriedade ajuda a diferenciar talhões homônimos.
        $mostrarPropriedade = $propriedadeId === null && $propriedades->count() > 1;

        return view('dashboard', [
            'propriedades' => $propriedades,
            'propriedadeSelecionada' => $propriedadeId,
            'areaTotal' => $indicadores->areaTotal($usuario, $propriedadeId),
            'quantidadeTalhoes' => $indicadores->quantidadeDeTalhoes($usuario, $propriedadeId),
            'resumo' => $resumo,
            'graficoComposicao' => [
                'rotulos' => ['Insumos', 'Maquinário', 'Mão de obra'],
                'valores' => [$resumo['custo_insumos'], $resumo['custo_maquinas'], $resumo['custo_mao_obra']],
            ],
            'graficoCustoPorHectare' => [
                'rotulos' => $custosPorTalhao
                    ->map(fn (array $custo): string => $mostrarPropriedade
                        ? $custo['talhao']->nome.' ('.$custo['talhao']->propriedade->nome.')'
                        : $custo['talhao']->nome)
                    ->values()
                    ->all(),
                'valores' => $custosPorTalhao
                    ->map(fn (array $custo): float => $custo['custo_por_hectare'] ?? 0)
                    ->values()
                    ->all(),
            ],
            'ultimasAtividades' => $indicadores->ultimasAtividades($usuario, $filtros),
        ]);
    }
}
