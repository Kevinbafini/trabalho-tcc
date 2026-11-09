<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ListagemRequest;
use App\Http\Resources\InsumoResource;
use App\Models\Insumo;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Consulta dos insumos (de onde saem os IDs usados no cadastro de atividades).
 */
class InsumoController extends Controller
{
    /**
     * Lista os insumos, com busca por nome e filtro por propriedade.
     */
    public function index(ListagemRequest $request, #[CurrentUser] User $usuario): AnonymousResourceCollection
    {
        $insumos = Insumo::query()
            ->doUsuario($usuario)
            ->when($request->propriedadeId(), fn (Builder $query, int $id) => $query->where('propriedade_id', $id))
            ->buscar($request->busca())
            ->orderBy('nome')
            ->paginate($request->porPagina())
            ->withQueryString();

        return InsumoResource::collection($insumos);
    }
}
