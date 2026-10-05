<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

/**
 * Busca textual simples para as listagens: procura o termo (parte do texto)
 * nas colunas informadas por camposDeBusca() de cada model.
 */
trait Pesquisavel
{
    /**
     * Colunas onde o termo será procurado.
     *
     * @return list<string>
     */
    abstract protected function camposDeBusca(): array;

    /**
     * Restringe a consulta aos registros que contêm o termo em alguma das colunas.
     * Um termo vazio não filtra nada.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function buscar(Builder $query, string $termo): void
    {
        $termo = trim($termo);

        if ($termo === '') {
            return;
        }

        $query->where(function (Builder $consulta) use ($termo): void {
            foreach ($this->camposDeBusca() as $campo) {
                $consulta->orWhereLike($this->qualifyColumn($campo), "%{$termo}%");
            }
        });
    }
}
