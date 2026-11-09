<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ListagemRequest;
use App\Http\Resources\PropriedadeResource;
use App\Models\Propriedade;
use App\Models\User;
use App\Services\IndicadoresCustoService;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Consulta das propriedades do usuário do token.
 */
class PropriedadeController extends Controller
{
    /**
     * Lista as propriedades, com busca por nome, proprietário ou município.
     */
    public function index(ListagemRequest $request, #[CurrentUser] User $usuario): AnonymousResourceCollection
    {
        $propriedades = Propriedade::query()
            ->doUsuario($usuario)
            ->buscar($request->busca())
            ->withCount('talhoes')
            ->orderBy('nome')
            ->paginate($request->porPagina())
            ->withQueryString();

        return PropriedadeResource::collection($propriedades);
    }

    /**
     * Detalhes da propriedade e o resumo dos custos das suas atividades.
     */
    public function show(Propriedade $propriedade, #[CurrentUser] User $usuario, IndicadoresCustoService $indicadores): PropriedadeResource
    {
        $propriedade->loadCount('talhoes');

        return (new PropriedadeResource($propriedade))->additional([
            'custos' => $indicadores->resumo($usuario, ['propriedade_id' => $propriedade->id]),
        ]);
    }
}
