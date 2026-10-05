<?php

namespace App\Http\Controllers;

use App\Http\Requests\FiltroCustosRequest;
use App\Models\Talhao;
use App\Models\User;
use App\Services\IndicadoresCustoService;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\View\View;

class RelatorioController extends Controller
{
    /**
     * Relatório de custos com filtros por propriedade, talhão, safra e período,
     * listando as atividades que formaram o custo.
     */
    public function __invoke(FiltroCustosRequest $request, #[CurrentUser] User $usuario, IndicadoresCustoService $indicadores): View
    {
        $filtros = $request->filtros();

        return view('relatorios.index', [
            'filtros' => $filtros,
            'resumo' => $indicadores->resumo($usuario, $filtros),
            'atividades' => $indicadores->atividades($usuario, $filtros)
                ->with(['talhao', 'propriedade'])
                ->orderByDesc('data')
                ->orderByDesc('id')
                ->paginate(20)
                ->withQueryString(),
            'propriedades' => $this->propriedadesDoUsuario($usuario),
            'talhoes' => Talhao::query()->doUsuario($usuario)->with('propriedade')->orderBy('nome')->get(),
            'safras' => $indicadores->safras($usuario),
        ]);
    }
}
