<?php

namespace Database\Factories;

use App\Enums\StatusEquipamento;
use App\Models\Implemento;
use App\Models\Propriedade;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Implemento>
 */
class ImplementoFactory extends Factory
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
            'nome' => fake()->randomElement(['Grade aradora', 'Plantadeira', 'Pulverizador de barras']),
            'tipo' => 'Preparo do solo',
            'modelo' => null,
            'status' => StatusEquipamento::Ativo,
        ];
    }
}
