<?php

namespace App\Models;

use App\Concerns\PertenceAPropriedade;
use App\Concerns\Pesquisavel;
use App\Enums\StatusEquipamento;
use Database\Factories\MaquinaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * Máquina da propriedade. O custo/hora compõe o custo de maquinário
 * (e já incorpora o custo operacional dos implementos que ela traciona).
 *
 * @property int $id
 * @property int $propriedade_id
 * @property string $nome
 * @property string $tipo
 * @property string|null $modelo
 * @property int|null $ano
 * @property float $custo_hora
 * @property StatusEquipamento $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Table('maquinas')]
#[Fillable(['nome', 'tipo', 'modelo', 'ano', 'custo_hora', 'status'])]
class Maquina extends Model
{
    /** @use HasFactory<MaquinaFactory> */
    use HasFactory, PertenceAPropriedade, Pesquisavel;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ano' => 'integer',
            'custo_hora' => 'float',
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
     * Atividades em que a máquina foi utilizada.
     *
     * @return BelongsToMany<AtividadeAgricola, $this>
     */
    public function atividades(): BelongsToMany
    {
        return $this->belongsToMany(AtividadeAgricola::class, 'atividade_maquina', 'maquina_id', 'atividade_agricola_id');
    }
}
