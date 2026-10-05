<?php

namespace App\Http\Controllers;

use App\Models\Propriedade;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

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

    /**
     * Resposta das listagens com busca em tempo real.
     *
     * Quando a requisição vem da busca (cabeçalho X-Busca-Em-Tempo-Real),
     * devolve apenas o trecho @fragment('resultados') da view; caso contrário,
     * devolve a página completa.
     */
    protected function responderListagem(Request $request, View $view): View|string
    {
        return $request->hasHeader('X-Busca-Em-Tempo-Real')
            ? $view->fragment('resultados')
            : $view;
    }
}
