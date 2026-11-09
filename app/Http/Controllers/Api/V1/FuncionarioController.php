<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ListagemRequest;
use App\Http\Resources\FuncionarioResource;
use App\Models\Funcionario;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Consulta dos funcionários (de onde saem os IDs usados no cadastro de atividades).
 */
class FuncionarioController extends Controller
{
    /**
     * Lista os funcionários, com busca (nome, função) e filtro por propriedade.
     */
    public function index(ListagemRequest $request, #[CurrentUser] User $usuario): AnonymousResourceCollection
    {
        $funcionarios = Funcionario::query()
            ->doUsuario($usuario)
            ->when($request->propriedadeId(), fn (Builder $query, int $id) => $query->where('propriedade_id', $id))
            ->buscar($request->busca())
            ->orderBy('nome')
            ->paginate($request->porPagina())
            ->withQueryString();

        return FuncionarioResource::collection($funcionarios);
    }
}
