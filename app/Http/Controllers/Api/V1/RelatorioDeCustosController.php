<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\FiltroCustosRequest;
use App\Http\Resources\RelatorioDeCustosResource;
use App\Models\User;
use App\Services\IndicadoresCustoService;
use Illuminate\Container\Attributes\CurrentUser;

/**
 * Relatório de custos agrícolas pela API.
 *
 * Usa os mesmos filtros (FiltroCustosRequest: só aceita propriedades e
 * talhões do usuário) e os mesmos cálculos (IndicadoresCustoService) da tela
 * de Relatórios.
 */
class RelatorioDeCustosController extends Controller
{
    /**
     * Totais por grupo de custo, custo por hectare e custos por talhão, por
     * tipo de operação, por mês e por cultura.
     */
    public function __invoke(FiltroCustosRequest $request, #[CurrentUser] User $usuario, IndicadoresCustoService $indicadores): RelatorioDeCustosResource
    {
        $filtros = $request->filtros();

        // Como na tela de Relatórios, só entram os talhões com atividades no filtro.
        $custosPorTalhao = $indicadores->custosPorTalhao($usuario, $filtros, somenteComAtividades: true);

        return new RelatorioDeCustosResource([
            'filtros' => $filtros,
            'resumo' => $indicadores->resumo($usuario, $filtros),
            'por_talhao' => $custosPorTalhao,
            'por_operacao' => $indicadores->custosPorOperacao($usuario, $filtros),
            'por_mes' => $indicadores->custosPorMes($usuario, $filtros),
            'por_cultura' => $indicadores->custosPorCultura($custosPorTalhao),
        ]);
    }
}
