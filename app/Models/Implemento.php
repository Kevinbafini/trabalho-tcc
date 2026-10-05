<?php

namespace App\Models;

use App\Concerns\PertenceAPropriedade;
use App\Concerns\Pesquisavel;
use App\Enums\StatusEquipamento;
use Database\Factories\ImplementoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * Implemento agrícola (grade, plantadeira, pulverizador de barras...).
 * Não possui custo próprio: é apenas associado às atividades.
 *
 * @property int $id
 * @property int $propriedade_id
 * @property string $nome
 * @property string $tipo
 * @property string|null $modelo
 * @property StatusEquipamento $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Table('implementos')]
#[Fillable(['nome', 'tipo', 'modelo', 'status'])]
class Implemento extends Model
{
    /** @use HasFactory<ImplementoFactory> */
    use HasFactory, PertenceAPropriedade, Pesquisavel;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => StatusEquipamento::class,
        ];
    }

    /**
     * Colunas usadas pela busca da listagem.
     *
     * @return list<string>
     */
    protected function camposDeBusca(): array
    {
        return ['nome', 'tipo', 'modelo'];
    }

    /**
     * Atividades em que o implemento foi utilizado.
     *
     * @return BelongsToMany<AtividadeAgricola, $this>
     */
    public function atividades(): BelongsToMany
    {
        return $this->belongsToMany(AtividadeAgricola::class, 'atividade_implemento', 'implemento_id', 'atividade_agricola_id');
    }
}
