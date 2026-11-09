<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ListagemRequest;
use App\Http\Resources\ImplementoResource;
use App\Models\Implemento;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Consulta dos implementos (de onde saem os IDs usados no cadastro de atividades).
 */
class ImplementoController extends Controller
{
    /**
     * Lista os implementos, com busca (nome, tipo, modelo) e filtro por propriedade.
     */
    public function index(ListagemRequest $request, #[CurrentUser] User $usuario): AnonymousResourceCollection
    {
        $implementos = Implemento::query()
            ->doUsuario($usuario)
            ->when($request->propriedadeId(), fn (Builder $query, int $id) => $query->where('propriedade_id', $id))
            ->buscar($request->busca())
            ->orderBy('nome')
            ->paginate($request->porPagina())
            ->withQueryString();

        return ImplementoResource::collection($implementos);
    }
}
