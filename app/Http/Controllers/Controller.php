<?php

namespace App\Http\Controllers;

use App\Models\Propriedade;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;

abstract class Controller
{
    /**
     * Propriedades do usuário, usadas nos filtros e nos campos de seleção.
     *
     * @return Collection<int, Propriedade>
     */
    protected function propriedadesDoUsuario(User $usuario): Collection
    {
        return $usuario->propriedades()->orderBy('nome')->get();
    }

    /**
     * Encaminha o usuário para o cadastro de propriedade quando ele ainda não
     * possui nenhuma (todos os demais cadastros dependem de uma propriedade).
     */
    protected function redirecionarParaCadastroDePropriedade(): RedirectResponse
    {
        return to_route('propriedades.create')
            ->with('erro', 'Cadastre uma propriedade antes de continuar. Todos os demais cadastros pertencem a uma propriedade.');
    }
}
