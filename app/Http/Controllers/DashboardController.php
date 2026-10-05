<?php

namespace App\Http\Controllers;

use App\Http\Requests\FiltroCustosRequest;
use App\Models\User;
use App\Services\GraficosCustoService;
use App\Services\IndicadoresCustoService;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Indicadores gerais, gráficos de custos e últimas atividades.
     */
    public function __invoke(
        FiltroCustosRequest $request,
        #[CurrentUser] User $usuario,
        IndicadoresCustoService $indicadores,
        GraficosCustoService $graficos,
    ): View {
        $propriedades = $this->propriedadesDoUsuario($usuario);
        $propriedadeId = $request->filtros()['propriedade_id'];
        $filtros = ['propriedade_id' => $propriedadeId];

        $resumo = $indicadores->resumo($usuario, $filtros);

        // Com mais de uma propriedade, o nome da propriedade ajuda a diferenciar talhões homônimos.
        $mostrarPropriedade = $propriedadeId === null && $propriedades->count() > 1;

        return view('dashboard', [
            'propriedades' => $propriedades,
            'propriedadeSelecionada' => $propriedadeId,
            'areaTotal' => $indicadores->areaTotal($usuario, $propriedadeId),
            'quantidadeTalhoes' => $indicadores->quantidadeDeTalhoes($usuario, $propriedadeId),
            'resumo' => $resumo,
            'graficoComposicao' => $graficos->composicao($resumo),
            'graficoCustoPorHectare' => $graficos->custoPorHectarePorTalhao(
                $indicadores->custosPorTalhao($usuario, $filtros),
                $mostrarPropriedade,
            ),
            'ultimasAtividades' => $indicadores->ultimasAtividades($usuario, $filtros),
        ]);
    }
}
