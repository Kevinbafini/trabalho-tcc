<?php

namespace Database\Factories;

use App\Models\Propriedade;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Propriedade>
 */
class PropriedadeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'nome' => 'Fazenda '.fake()->lastName(),
            'proprietario' => fake()->name(),
            'municipio' => fake()->city(),
            'uf' => fake()->randomElement(['SP', 'MG', 'PR', 'GO', 'MT']),
            'area_total' => 100,
            'observacoes' => null,
        ];
    }
}
