<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ListagemRequest;
use App\Http\Resources\TalhaoResource;
use App\Models\Talhao;
use App\Models\User;
use App\Services\IndicadoresCustoService;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Consulta dos talhões do usuário do token.
 */
class TalhaoController extends Controller
{
    /**
     * Lista os talhões, com busca (nome, cultura, safra) e filtro por propriedade.
     */
    public function index(ListagemRequest $request, #[CurrentUser] User $usuario): AnonymousResourceCollection
    {
        $talhoes = Talhao::query()
            ->doUsuario($usuario)
            ->with('propriedade')
            ->when($request->propriedadeId(), fn (Builder $query, int $id) => $query->where('propriedade_id', $id))
            ->buscar($request->busca())
            ->orderBy('nome')
            ->paginate($request->porPagina())
            ->withQueryString();

        return TalhaoResource::collection($talhoes);
    }

    /**
     * Detalhes do talhão e o resumo dos custos, com o custo por hectare.
     */
    public function show(Talhao $talhao, #[CurrentUser] User $usuario, IndicadoresCustoService $indicadores): TalhaoResource
    {
        return (new TalhaoResource($talhao->load('propriedade')))->additional([
            'custos' => $indicadores->resumo($usuario, ['talhao_id' => $talhao->id]),
        ]);
    }
}
