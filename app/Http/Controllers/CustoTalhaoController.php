<?php

namespace App\Http\Controllers;

use App\Http\Requests\FiltroCustosRequest;
use App\Models\User;
use App\Services\IndicadoresCustoService;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\View\View;

class CustoTalhaoController extends Controller
{
    /**
     * Custos acumulados e custo por hectare de cada talhão.
     */
    public function __invoke(FiltroCustosRequest $request, #[CurrentUser] User $usuario, IndicadoresCustoService $indicadores): View
    {
        $filtrosValidados = $request->filtros();
        $filtros = [
            'propriedade_id' => $filtrosValidados['propriedade_id'],
            'safra' => $filtrosValidados['safra'],
        ];

        return view('custos-talhoes.index', [
            'filtros' => $filtros,
            'custosPorTalhao' => $indicadores->custosPorTalhao($usuario, $filtros),
            'resumo' => $indicadores->resumo($usuario, $filtros),
            'propriedades' => $this->propriedadesDoUsuario($usuario),
            'safras' => $indicadores->safras($usuario),
        ]);
    }
}
