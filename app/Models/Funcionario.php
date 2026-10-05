<?php

namespace App\Models;

use App\Concerns\PertenceAPropriedade;
use App\Enums\StatusRecurso;
use Database\Factories\FuncionarioFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * Funcionário da propriedade. O custo/hora compõe o custo de mão de obra.
 *
 * @property int $id
 * @property int $propriedade_id
 * @property string $nome
 * @property string $funcao
 * @property float $custo_hora
 * @property StatusRecurso $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Table('funcionarios')]
#[Fillable(['nome', 'funcao', 'custo_hora', 'status'])]
class Funcionario extends Model
{
    /** @use HasFactory<FuncionarioFactory> */
    use HasFactory, PertenceAPropriedade;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'custo_hora' => 'float',
            'status' => StatusRecurso::class,
        ];
    }

    /**
     * Atividades em que o funcionário trabalhou.
     *
     * @return BelongsToMany<AtividadeAgricola, $this>
     */
    public function atividades(): BelongsToMany
    {
        return $this->belongsToMany(AtividadeAgricola::class, 'atividade_funcionario', 'funcionario_id', 'atividade_agricola_id');
    }
}
