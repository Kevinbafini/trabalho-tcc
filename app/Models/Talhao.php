<?php

namespace App\Models;

use App\Concerns\PertenceAPropriedade;
use App\Enums\StatusTalhao;
use Database\Factories\TalhaoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Talhão: divisão da propriedade onde as atividades são realizadas.
 * O custo por hectare é calculado sobre a área do talhão.
 *
 * @property int $id
 * @property int $propriedade_id
 * @property string $nome
 * @property float $area
 * @property string $cultura
 * @property string $safra
 * @property StatusTalhao $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Table('talhoes')]
#[Fillable(['nome', 'area', 'cultura', 'safra', 'status'])]
class Talhao extends Model
{
    /** @use HasFactory<TalhaoFactory> */
    use HasFactory, PertenceAPropriedade;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'area' => 'float',
            'status' => StatusTalhao::class,
        ];
    }

    /**
     * @return HasMany<AtividadeAgricola, $this>
     */
    public function atividades(): HasMany
    {
        return $this->hasMany(AtividadeAgricola::class);
    }
}
