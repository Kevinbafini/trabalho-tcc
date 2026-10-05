<?php

namespace Database\Factories;

use App\Enums\StatusTalhao;
use App\Models\Propriedade;
use App\Models\Talhao;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Talhao>
 */
class TalhaoFactory extends Factory
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
            'nome' => 'Talhão '.fake()->unique()->numberBetween(1, 9999),
            'area' => 10,
            'cultura' => fake()->randomElement(['Soja', 'Milho', 'Amendoim']),
            'safra' => '2025/2026',
            'status' => StatusTalhao::EmCultivo,
        ];
    }
}
