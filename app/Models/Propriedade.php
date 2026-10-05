<?php

namespace App\Models;

use App\Concerns\Pesquisavel;
use App\Enums\StatusTalhao;
use Database\Factories\PropriedadeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Propriedade rural de um usuário. É a raiz de todos os dados do sistema:
 * talhões, recursos e atividades pertencem a uma propriedade.
 *
 * @property int $id
 * @property int $user_id
 * @property string $nome
 * @property string $proprietario
 * @property string $municipio
 * @property string $uf
 * @property float $area_total
 * @property string|null $observacoes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Table('propriedades')]
#[Fillable(['nome', 'proprietario', 'municipio', 'uf', 'area_total', 'observacoes'])]
class Propriedade extends Model
{
    /** @use HasFactory<PropriedadeFactory> */
    use HasFactory, Pesquisavel;

    /**
     * Unidades federativas aceitas no cadastro.
     *
     * @var list<string>
     */
    public const UFS = [
        'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA',
        'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'area_total' => 'float',
        ];
    }

    /**
     * Colunas usadas pela busca da listagem.
     *
     * @return list<string>
     */
    protected function camposDeBusca(): array
    {
        return ['nome', 'proprietario', 'municipio'];
    }

    /**
     * Usuário dono da propriedade.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Talhao, $this>
     */
    public function talhoes(): HasMany
    {
        return $this->hasMany(Talhao::class);
    }

    /**
     * @return HasMany<Funcionario, $this>
     */
    public function funcionarios(): HasMany
    {
        return $this->hasMany(Funcionario::class);
    }

    /**
     * @return HasMany<Maquina, $this>
     */
    public function maquinas(): HasMany
    {
        return $this->hasMany(Maquina::class);
    }

    /**
     * @return HasMany<Implemento, $this>
     */
    public function implementos(): HasMany
    {
        return $this->hasMany(Implemento::class);
    }

    /**
     * @return HasMany<Insumo, $this>
     */
    public function insumos(): HasMany
    {
        return $this->hasMany(Insumo::class);
    }

    /**
     * @return HasMany<AtividadeAgricola, $this>
     */
    public function atividades(): HasMany
    {
        return $this->hasMany(AtividadeAgricola::class);
    }

    /**
     * Restringe a consulta às propriedades do usuário.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function doUsuario(Builder $query, User $user): void
    {
        $query->whereBelongsTo($user);
    }

    /**
     * Ao localizar a propriedade pela URL, procura apenas entre as propriedades
     * do usuário logado. Propriedades de outros usuários resultam em 404.
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

    /**
     * Soma das áreas dos talhões que ocupam a propriedade (todos, exceto os inativos).
     *
     * @param  Talhao|null  $exceto  Talhão a desconsiderar (o próprio talhão em edição).
     */
    public function areaOcupada(?Talhao $exceto = null): float
    {
        return round((float) $this->talhoes()
            ->where('status', '!=', StatusTalhao::Inativo)
            ->when($exceto, fn (Builder $query, Talhao $talhao) => $query->whereKeyNot($talhao->getKey()))
            ->sum('area'), 2);
    }

    /**
     * Indica se a propriedade possui talhões, recursos ou atividades cadastrados.
     */
    public function possuiDadosVinculados(): bool
    {
        return $this->talhoes()->exists()
            || $this->funcionarios()->exists()
            || $this->maquinas()->exists()
            || $this->implementos()->exists()
            || $this->insumos()->exists()
            || $this->atividades()->exists();
    }
}
