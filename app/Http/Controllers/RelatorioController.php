<?php

namespace App\Http\Controllers;

use App\Http\Requests\FiltroCustosRequest;
use App\Models\Talhao;
use App\Models\User;
use App\Services\GraficosCustoService;
use App\Services\IndicadoresCustoService;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\View\View;

class RelatorioController extends Controller
{
    /**
     * Relatório de custos com filtros por propriedade, talhão, safra e período,
     * gráficos e a lista das atividades que formaram o custo.
     */
    public function __invoke(
        FiltroCustosRequest $request,
        #[CurrentUser] User $usuario,
        IndicadoresCustoService $indicadores,
        GraficosCustoService $graficos,
    ): View {
        $filtros = $request->filtros();
        $propriedades = $this->propriedadesDoUsuario($usuario);
        $resumo = $indicadores->resumo($usuario, $filtros);
        $mostrarPropriedade = $filtros['propriedade_id'] === null && $propriedades->count() > 1;

        // No relatório, só entram os talhões que tiveram atividades no período filtrado.
        $talhoesComAtividades = $indicadores->custosPorTalhao($usuario, $filtros, somenteComAtividades: true);

        return view('relatorios.index', [
            'filtros' => $filtros,
            'resumo' => $resumo,
            'atividades' => $indicadores->atividades($usuario, $filtros)
                ->with(['talhao', 'propriedade'])
                ->orderByDesc('data')
                ->orderByDesc('id')
                ->paginate(20)
                ->withQueryString(),
            'propriedades' => $propriedades,
            'talhoes' => Talhao::query()->doUsuario($usuario)->with('propriedade')->orderBy('nome')->get(),
            'safras' => $indicadores->safras($usuario),
            'graficoComposicao' => $graficos->composicao($resumo),
            'graficoMensal' => $graficos->custosPorMes($indicadores->custosPorMes($usuario, $filtros)),
            'graficoOperacoes' => $graficos->custosPorOperacao($indicadores->custosPorOperacao($usuario, $filtros)),
            'graficoCustoPorHectare' => $graficos->custoPorHectarePorTalhao($talhoesComAtividades, $mostrarPropriedade),
        ]);
    }
}
