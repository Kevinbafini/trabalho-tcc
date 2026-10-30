<?php

namespace App\Models;

use App\Enums\ProvedorIa;
use Database\Factories\ConversaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Conversa do usuário com o Assistente IA.
 *
 * @property int $id
 * @property int $user_id
 * @property string $titulo
 * @property ProvedorIa $provedor provedor usado na última resposta (sugerido no seletor)
 * @property Carbon|null $ultima_mensagem_em
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['titulo', 'provedor', 'ultima_mensagem_em'])]
class Conversa extends Model
{
    /** @use HasFactory<ConversaFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'provedor' => ProvedorIa::class,
            'ultima_mensagem_em' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mensagens em ordem de envio.
     *
     * @return HasMany<Mensagem, $this>
     */
    public function mensagens(): HasMany
    {
        return $this->hasMany(Mensagem::class)->orderBy('id');
    }

    /**
     * Nas rotas, a conversa só é encontrada entre as do usuário logado
     * (a de outro usuário responde 404, como os demais cadastros).
     */
    public function resolveRouteBinding($value, $field = null): ?Model
    {
        return $this->newQuery()
            ->where('user_id', Auth::id())
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->firstOrFail();
    }
}
