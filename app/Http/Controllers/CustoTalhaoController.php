<?php

namespace App\Http\Controllers;

use App\Http\Requests\FiltroCustosRequest;
use App\Models\User;
use App\Services\GraficosCustoService;
use App\Services\IndicadoresCustoService;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\View\View;

class CustoTalhaoController extends Controller
{
    /**
     * Custos acumulados e custo por hectare de cada talhão, com gráficos.
     */
    public function __invoke(
        FiltroCustosRequest $request,
        #[CurrentUser] User $usuario,
        IndicadoresCustoService $indicadores,
        GraficosCustoService $graficos,
    ): View {
        $filtrosValidados = $request->filtros();
        $filtros = [
            'propriedade_id' => $filtrosValidados['propriedade_id'],
            'safra' => $filtrosValidados['safra'],
        ];

        $propriedades = $this->propriedadesDoUsuario($usuario);
        $resumo = $indicadores->resumo($usuario, $filtros);
        $custosPorTalhao = $indicadores->custosPorTalhao($usuario, $filtros);
        $mostrarPropriedade = $filtros['propriedade_id'] === null && $propriedades->count() > 1;

        return view('custos-talhoes.index', [
            'filtros' => $filtros,
            'custosPorTalhao' => $custosPorTalhao,
            'resumo' => $resumo,
            'propriedades' => $propriedades,
            'safras' => $indicadores->safras($usuario),
            'graficoComposicao' => $graficos->composicao($resumo),
            'graficoCustoTotalPorTalhao' => $graficos->custoTotalPorTalhao($custosPorTalhao, $mostrarPropriedade),
            'graficoCustoPorHectare' => $graficos->custoPorHectarePorTalhao($custosPorTalhao, $mostrarPropriedade),
            'graficoCustoPorCultura' => $graficos->custoPorHectarePorCultura($indicadores->custosPorCultura($custosPorTalhao)),
        ]);
    }
}
