<?php

namespace App\Models;

use App\Concerns\PertenceAPropriedade;
use App\Enums\TipoOperacao;
use Carbon\CarbonInterface;
use Database\Factories\AtividadeAgricolaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * Atividade agrícola realizada em um talhão.
 *
 * Os campos de custo e a safra são gravados pelo CustoAtividadeService
 * no momento do lançamento e não mudam quando os cadastros mudam.
 *
 * @property int $id
 * @property int $propriedade_id
 * @property int $talhao_id
 * @property CarbonInterface $data
 * @property TipoOperacao $tipo
 * @property string $safra
 * @property string|null $descricao
 * @property float $custo_insumos
 * @property float $custo_maquinas
 * @property float $custo_mao_obra
 * @property float $custo_total
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Table('atividades_agricolas')]
#[Fillable(['data', 'tipo', 'descricao'])]
class AtividadeAgricola extends Model
{
    /** @use HasFactory<AtividadeAgricolaFactory> */
    use HasFactory, PertenceAPropriedade;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data' => 'date',
            'tipo' => TipoOperacao::class,
            'custo_insumos' => 'float',
            'custo_maquinas' => 'float',
            'custo_mao_obra' => 'float',
            'custo_total' => 'float',
        ];
    }

    /**
     * @return BelongsTo<Talhao, $this>
     */
    public function talhao(): BelongsTo
    {
        return $this->belongsTo(Talhao::class);
    }

    /**
     * Insumos aplicados, com quantidade e custo registrados no lançamento.
     *
     * @return BelongsToMany<Insumo, $this>
     */
    public function insumos(): BelongsToMany
    {
        return $this->belongsToMany(Insumo::class, 'atividade_insumo', 'atividade_agricola_id', 'insumo_id')
            ->withPivot(['quantidade', 'custo_unitario', 'custo_total']);
    }

    /**
     * Máquinas utilizadas, com horas e custo/hora registrados no lançamento.
     *
     * @return BelongsToMany<Maquina, $this>
     */
    public function maquinas(): BelongsToMany
    {
        return $this->belongsToMany(Maquina::class, 'atividade_maquina', 'atividade_agricola_id', 'maquina_id')
            ->withPivot(['horas', 'custo_hora', 'custo_total']);
    }

    /**
     * Funcionários envolvidos, com horas e custo/hora registrados no lançamento.
     *
     * @return BelongsToMany<Funcionario, $this>
     */
    public function funcionarios(): BelongsToMany
    {
        return $this->belongsToMany(Funcionario::class, 'atividade_funcionario', 'atividade_agricola_id', 'funcionario_id')
            ->withPivot(['horas', 'custo_hora', 'custo_total']);
    }

    /**
     * Implementos utilizados (sem custo próprio).
     *
     * @return BelongsToMany<Implemento, $this>
     */
    public function implementos(): BelongsToMany
    {
        return $this->belongsToMany(Implemento::class, 'atividade_implemento', 'atividade_agricola_id', 'implemento_id');
    }
}
