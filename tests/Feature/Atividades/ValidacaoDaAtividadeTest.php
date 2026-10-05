<?php

use App\Models\AtividadeAgricola;
use App\Models\Funcionario;
use App\Models\Implemento;
use App\Models\Insumo;
use App\Models\Maquina;
use App\Models\Propriedade;
use App\Models\Talhao;

beforeEach(function () {
    $this->cenario = criarPropriedadeComRecursos();
    $this->outraPropriedade = Propriedade::factory()->for($this->cenario['usuario'])->create();
    $this->actingAs($this->cenario['usuario']);
});

test('rejeita recursos que pertencem a outra propriedade do mesmo usuário', function (string $modelo, Closure $campos, string $campoComErro, string $mensagem) {
    $recurso = $modelo::factory()->for($this->outraPropriedade)->create();

    $this->post(route('atividades.store'), dadosDaAtividade($this->cenario, $campos($recurso)))
        ->assertSessionHasErrors([$campoComErro => $mensagem]);

    $this->assertDatabaseCount('atividades_agricolas', 0);
})->with([
    'talhão' => [Talhao::class, fn ($talhao) => ['talhao_id' => $talhao->id], 'talhao_id', 'O talhão selecionado não pertence a esta propriedade.'],
    'insumo' => [Insumo::class, fn ($insumo) => ['insumos' => [['insumo_id' => $insumo->id, 'quantidade' => 10]]], 'insumos.0.insumo_id', 'O insumo selecionado não pertence a esta propriedade.'],
    'máquina' => [Maquina::class, fn ($maquina) => ['maquinas' => [['maquina_id' => $maquina->id, 'horas' => 1]]], 'maquinas.0.maquina_id', 'A máquina selecionada não pertence a esta propriedade.'],
    'funcionário' => [Funcionario::class, fn ($funcionario) => ['funcionarios' => [['funcionario_id' => $funcionario->id, 'horas' => 1]]], 'funcionarios.0.funcionario_id', 'O funcionário selecionado não pertence a esta propriedade.'],
    'implemento' => [Implemento::class, fn ($implemento) => ['implementos' => [$implemento->id]], 'implementos.0', 'O implemento selecionado não pertence a esta propriedade.'],
]);

test('na edição, rejeita recursos de outra propriedade', function () {
    $this->post(route('atividades.store'), dadosDaAtividade($this->cenario))->assertSessionHasNoErrors();
    $atividade = AtividadeAgricola::sole();
    $insumoDeOutraPropriedade = Insumo::factory()->for($this->outraPropriedade)->create();

    $this->put(route('atividades.update', $atividade), dadosDaAtividade($this->cenario, [
        'insumos' => [['insumo_id' => $insumoDeOutraPropriedade->id, 'quantidade' => 10]],
    ]))->assertSessionHasErrors(['insumos.0.insumo_id' => 'O insumo selecionado não pertence a esta propriedade.']);

    expect($atividade->fresh()->custo_total)->toBe(1335.5);
});

test('exige pelo menos um insumo, máquina ou funcionário', function () {
    $this->post(route('atividades.store'), dadosDaAtividade($this->cenario, [
        'insumos' => [],
        'maquinas' => [],
        'funcionarios' => [],
    ]))->assertSessionHasErrors(['recursos' => 'Adicione pelo menos um insumo, uma máquina ou um funcionário para calcular o custo da atividade.']);
});

test('rejeita data futura', function () {
    $this->post(route('atividades.store'), dadosDaAtividade($this->cenario, ['data' => now()->addDay()->toDateString()]))
        ->assertSessionHasErrors(['data' => 'A data da atividade não pode ser uma data futura.']);
});

test('rejeita o mesmo insumo adicionado duas vezes', function () {
    $this->post(route('atividades.store'), dadosDaAtividade($this->cenario, [
        'insumos' => [
            ['insumo_id' => $this->cenario['ureia']->id, 'quantidade' => 100],
            ['insumo_id' => $this->cenario['ureia']->id, 'quantidade' => 50],
        ],
    ]))->assertSessionHasErrors(['insumos.0.insumo_id' => 'Este insumo foi adicionado mais de uma vez.']);
});

test('rejeita quantidades e horas zeradas', function () {
    $this->post(route('atividades.store'), dadosDaAtividade($this->cenario, [
        'insumos' => [['insumo_id' => $this->cenario['ureia']->id, 'quantidade' => 0]],
        'maquinas' => [['maquina_id' => $this->cenario['trator']->id, 'horas' => 0]],
    ]))->assertSessionHasErrors([
        'insumos.0.quantidade' => 'O campo quantidade deve ser maior que 0.',
        'maquinas.0.horas' => 'O campo horas da máquina deve ser maior que 0.',
    ]);
});

test('exibe mensagens amigáveis para os campos obrigatórios', function () {
    $this->post(route('atividades.store'), [
        'propriedade_id' => $this->cenario['propriedade']->id,
        'insumos' => [['insumo_id' => $this->cenario['ureia']->id, 'quantidade' => 1]],
    ])->assertSessionHasErrors([
        'talhao_id' => 'O campo talhão é obrigatório.',
        'data' => 'O campo data é obrigatório.',
        'tipo' => 'O campo tipo de operação é obrigatório.',
    ]);
});
