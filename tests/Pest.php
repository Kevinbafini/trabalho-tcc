<?php

use App\Models\Funcionario;
use App\Models\Implemento;
use App\Models\Insumo;
use App\Models\Maquina;
use App\Models\Propriedade;
use App\Models\Talhao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/*
|--------------------------------------------------------------------------
| Cenário padrão dos testes de custos
|--------------------------------------------------------------------------
|
| Um usuário com uma propriedade de 50 ha, um talhão de soja de 12,5 ha (safra
| 2025/2026) e recursos com valores conhecidos:
|   Ureia R$ 3,20/kg · Calcário R$ 150,00/t · Trator R$ 180,00/h
|   Operador R$ 28,50/h · Grade (implemento, sem custo)
|
*/

/**
 * @return array{
 *     usuario: User,
 *     propriedade: Propriedade,
 *     talhao: Talhao,
 *     ureia: Insumo,
 *     calcario: Insumo,
 *     trator: Maquina,
 *     operador: Funcionario,
 *     grade: Implemento,
 * }
 */
function criarPropriedadeComRecursos(?User $usuario = null): array
{
    $usuario ??= User::factory()->create();
    $propriedade = Propriedade::factory()->for($usuario)->create(['area_total' => 50]);

    return [
        'usuario' => $usuario,
        'propriedade' => $propriedade,
        'talhao' => Talhao::factory()->for($propriedade)->create(['nome' => 'Talhão 01', 'area' => 12.5, 'cultura' => 'Soja', 'safra' => '2025/2026']),
        'ureia' => Insumo::factory()->for($propriedade)->create(['nome' => 'Ureia', 'valor_unitario' => 3.20]),
        'calcario' => Insumo::factory()->for($propriedade)->create(['nome' => 'Calcário', 'valor_unitario' => 150.00]),
        'trator' => Maquina::factory()->for($propriedade)->create(['nome' => 'Trator 01', 'custo_hora' => 180.00]),
        'operador' => Funcionario::factory()->for($propriedade)->create(['nome' => 'Operador', 'custo_hora' => 28.50]),
        'grade' => Implemento::factory()->for($propriedade)->create(['nome' => 'Grade aradora']),
    ];
}

/**
 * Dados do formulário de atividade: 250 kg de ureia, 2,5 h de trator e 3 h
 * de operador (R$ 800,00 + R$ 450,00 + R$ 85,50 = R$ 1.335,50).
 *
 * @param  array<string, mixed>  $cenario
 * @param  array<string, mixed>  $alteracoes
 * @return array<string, mixed>
 */
function dadosDaAtividade(array $cenario, array $alteracoes = []): array
{
    return array_replace([
        'propriedade_id' => $cenario['propriedade']->id,
        'talhao_id' => $cenario['talhao']->id,
        'data' => '2026-03-10',
        'tipo' => 'adubacao',
        'descricao' => 'Adubação de cobertura',
        'insumos' => [['insumo_id' => $cenario['ureia']->id, 'quantidade' => 250]],
        'maquinas' => [['maquina_id' => $cenario['trator']->id, 'horas' => 2.5]],
        'funcionarios' => [['funcionario_id' => $cenario['operador']->id, 'horas' => 3]],
        'implementos' => [$cenario['grade']->id],
    ], $alteracoes);
}
