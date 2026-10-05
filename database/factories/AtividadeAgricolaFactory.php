<?php

namespace Database\Factories;

use App\Enums\TipoOperacao;
use App\Models\AtividadeAgricola;
use App\Models\Talhao;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Cria atividades sem recursos. Para atividades com custos calculados,
 * use o CustoAtividadeService.
 *
 * @extends Factory<AtividadeAgricola>
 */
class AtividadeAgricolaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'talhao_id' => Talhao::factory(),
            'propriedade_id' => fn (array $atributos) => Talhao::query()->whereKey($atributos['talhao_id'])->value('propriedade_id'),
            'safra' => fn (array $atributos) => Talhao::query()->whereKey($atributos['talhao_id'])->value('safra'),
            'data' => fake()->dateTimeBetween('-6 months', 'now'),
            'tipo' => fake()->randomElement(TipoOperacao::cases()),
            'descricao' => null,
            'custo_insumos' => 0,
            'custo_maquinas' => 0,
            'custo_mao_obra' => 0,
            'custo_total' => 0,
        ];
    }
}
