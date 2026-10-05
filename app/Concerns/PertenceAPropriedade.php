<?php

namespace App\Concerns;

use App\Models\Propriedade;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Comportamento comum dos registros que pertencem a uma propriedade
 * (talhões, recursos e atividades).
 *
 * Garante o isolamento entre usuários: cada usuário só enxerga e altera
 * os dados das suas próprias propriedades.
 */
trait PertenceAPropriedade
{
    /**
     * Propriedade dona do registro.
     *
     * @return BelongsTo<Propriedade, $this>
     */
    public function propriedade(): BelongsTo
    {
        return $this->belongsTo(Propriedade::class);
    }

    /**
     * Restringe a consulta aos registros das propriedades do usuário.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function doUsuario(Builder $query, User $user): void
    {
        $query->whereIn($this->qualifyColumn('propriedade_id'), $user->propriedades()->select('id'));
    }

    /**
     * Ao localizar o registro pela URL (route model binding), procura apenas
     * entre os registros do usuário logado. Registros de outros usuários
     * resultam em "página não encontrada" (404).
     *
     * @param  mixed  $value
     * @param  string|null  $field
     */
    public function resolveRouteBinding($value, $field = null): ?Model
    {
        /** @var User $usuario */
        $usuario = Auth::user();

        return $this->newQuery()
            ->doUsuario($usuario)
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->firstOrFail();
    }
}
