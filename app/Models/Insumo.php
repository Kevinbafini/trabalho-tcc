<?php

namespace App\Models;

use App\Concerns\PertenceAPropriedade;
use App\Enums\CategoriaInsumo;
use App\Enums\StatusRecurso;
use App\Enums\UnidadeMedida;
use Database\Factories\InsumoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * Insumo agrícola (semente, fertilizante, defensivo...).
 * A quantidade em estoque é apenas informativa: não há baixa automática.
 *
 * @property int $id
 * @property int $propriedade_id
 * @property string $nome
 * @property CategoriaInsumo $categoria
 * @property UnidadeMedida $unidade
 * @property float $valor_unitario
 * @property float $quantidade_estoque
 * @property StatusRecurso $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Table('insumos')]
#[Fillable(['nome', 'categoria', 'unidade', 'valor_unitario', 'quantidade_estoque', 'status'])]
class Insumo extends Model
{
    /** @use HasFactory<InsumoFactory> */
    use HasFactory, PertenceAPropriedade;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'categoria' => CategoriaInsumo::class,
            'unidade' => UnidadeMedida::class,
            'valor_unitario' => 'float',
            'quantidade_estoque' => 'float',
            'status' => StatusRecurso::class,
        ];
    }

    /**
     * Atividades em que o insumo foi aplicado.
     *
     * @return BelongsToMany<AtividadeAgricola, $this>
     */
    public function atividades(): BelongsToMany
    {
        return $this->belongsToMany(AtividadeAgricola::class, 'atividade_insumo', 'insumo_id', 'atividade_agricola_id');
    }
}
