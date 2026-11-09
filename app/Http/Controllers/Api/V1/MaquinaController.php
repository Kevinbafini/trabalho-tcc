<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ListagemRequest;
use App\Http\Resources\MaquinaResource;
use App\Models\Maquina;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Consulta das máquinas (de onde saem os IDs usados no cadastro de atividades).
 */
class MaquinaController extends Controller
{
    /**
     * Lista as máquinas, com busca (nome, tipo, modelo) e filtro por propriedade.
     */
    public function index(ListagemRequest $request, #[CurrentUser] User $usuario): AnonymousResourceCollection
    {
        $maquinas = Maquina::query()
            ->doUsuario($usuario)
            ->when($request->propriedadeId(), fn (Builder $query, int $id) => $query->where('propriedade_id', $id))
            ->buscar($request->busca())
            ->orderBy('nome')
            ->paginate($request->porPagina())
            ->withQueryString();

        return MaquinaResource::collection($maquinas);
    }
}
