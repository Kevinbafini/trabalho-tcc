<?php

namespace Database\Factories;

use App\Enums\CategoriaInsumo;
use App\Enums\StatusRecurso;
use App\Enums\UnidadeMedida;
use App\Models\Insumo;
use App\Models\Propriedade;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Insumo>
 */
class InsumoFactory extends Factory
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
            'nome' => fake()->randomElement(['Ureia', 'Calcário dolomítico', 'Glifosato', 'Semente de soja']),
            'categoria' => fake()->randomElement(CategoriaInsumo::cases()),
            'unidade' => UnidadeMedida::Quilograma,
            'valor_unitario' => fake()->randomFloat(2, 1, 100),
            'quantidade_estoque' => fake()->randomFloat(3, 0, 1000),
            'status' => StatusRecurso::Ativo,
        ];
    }
}
