<?php

namespace Database\Factories;

use App\Enums\StatusRecurso;
use App\Models\Funcionario;
use App\Models\Propriedade;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Funcionario>
 */
class FuncionarioFactory extends Factory
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
            'nome' => fake()->name(),
            'funcao' => fake()->randomElement(['Operador de máquinas', 'Trabalhador rural', 'Encarregado']),
            'custo_hora' => fake()->randomFloat(2, 15, 45),
            'status' => StatusRecurso::Ativo,
        ];
    }
}
