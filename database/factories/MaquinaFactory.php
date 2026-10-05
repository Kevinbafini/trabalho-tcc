<?php

namespace Database\Factories;

use App\Enums\StatusEquipamento;
use App\Models\Maquina;
use App\Models\Propriedade;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Maquina>
 */
class MaquinaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'propriedade_id' => Propriedade::factory(),
            'nome' => 'Trator '.fake()->numerify('####'),
            'tipo' => 'Trator',
            'modelo' => fake()->bothify('MF-####'),
            'ano' => fake()->numberBetween(2005, 2025),
            'custo_hora' => fake()->randomFloat(2, 80, 400),
            'status' => StatusEquipamento::Ativo,
        ];
    }
}
