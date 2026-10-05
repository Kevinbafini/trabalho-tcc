<?php

use App\Models\AtividadeAgricola;

beforeEach(function () {
    $this->cenario = criarPropriedadeComRecursos();
    $this->actingAs($this->cenario['usuario']);
});

test('calcula o custo do insumo multiplicando a quantidade pelo custo unitário', function () {
    $this->post(route('atividades.store'), dadosDaAtividade($this->cenario, [
        'insumos' => [
            ['insumo_id' => $this->cenario['ureia']->id, 'quantidade' => 250],
            ['insumo_id' => $this->cenario['calcario']->id, 'quantidade' => 2],
        ],
        'maquinas' => [],
        'funcionarios' => [],
    ]))->assertSessionHasNoErrors();

    $atividade = AtividadeAgricola::sole();

    $this->assertDatabaseHas('atividade_insumo', [
        'atividade_agricola_id' => $atividade->id,
        'insumo_id' => $this->cenario['ureia']->id,
        'quantidade' => 250,
        'custo_unitario' => 3.20,
        'custo_total' => 800.00,
    ]);
    $this->assertDatabaseHas('atividade_insumo', [
        'atividade_agricola_id' => $atividade->id,
        'insumo_id' => $this->cenario['calcario']->id,
        'quantidade' => 2,
        'custo_unitario' => 150.00,
        'custo_total' => 300.00,
    ]);
    expect($atividade->custo_insumos)->toBe(1100.0);
});

test('calcula o custo da máquina multiplicando as horas pelo custo por hora', function () {
    $this->post(route('atividades.store'), dadosDaAtividade($this->cenario, [
        'insumos' => [],
        'funcionarios' => [],
    ]))->assertSessionHasNoErrors();

    $atividade = AtividadeAgricola::sole();

    $this->assertDatabaseHas('atividade_maquina', [
        'atividade_agricola_id' => $atividade->id,
        'maquina_id' => $this->cenario['trator']->id,
        'horas' => 2.5,
        'custo_hora' => 180.00,
        'custo_total' => 450.00,
    ]);
    expect($atividade->custo_maquinas)->toBe(450.0);
});

test('calcula o custo da mão de obra multiplicando as horas trabalhadas pelo custo por hora', function () {
    $this->post(route('atividades.store'), dadosDaAtividade($this->cenario, [
        'insumos' => [],
        'maquinas' => [],
    ]))->assertSessionHasNoErrors();

    $atividade = AtividadeAgricola::sole();

    $this->assertDatabaseHas('atividade_funcionario', [
        'atividade_agricola_id' => $atividade->id,
        'funcionario_id' => $this->cenario['operador']->id,
        'horas' => 3,
        'custo_hora' => 28.50,
        'custo_total' => 85.50,
    ]);
    expect($atividade->custo_mao_obra)->toBe(85.5);
});

test('soma insumos, máquinas e mão de obra no custo total da atividade', function () {
    $resposta = $this->post(route('atividades.store'), dadosDaAtividade($this->cenario));

    $atividade = AtividadeAgricola::sole();

    expect($atividade->custo_insumos)->toBe(800.0)
        ->and($atividade->custo_maquinas)->toBe(450.0)
        ->and($atividade->custo_mao_obra)->toBe(85.5)
        ->and($atividade->custo_total)->toBe(1335.5);

    $resposta->assertRedirect(route('atividades.show', $atividade));
    expect(session('sucesso'))->toContain('1.335,50');
});

test('ignora custos enviados pelo navegador e recalcula no servidor', function () {
    $this->post(route('atividades.store'), dadosDaAtividade($this->cenario, [
        'insumos' => [['insumo_id' => $this->cenario['ureia']->id, 'quantidade' => 250, 'custo_unitario' => 0.01, 'custo_total' => 1]],
        'custo_total' => 1,
    ]))->assertSessionHasNoErrors();

    $atividade = AtividadeAgricola::sole();

    expect($atividade->custo_total)->toBe(1335.5);
    $this->assertDatabaseHas('atividade_insumo', ['insumo_id' => $this->cenario['ureia']->id, 'custo_unitario' => 3.20]);
});

test('associa implementos à atividade sem gerar custo', function () {
    $this->post(route('atividades.store'), dadosDaAtividade($this->cenario))->assertSessionHasNoErrors();

    $atividade = AtividadeAgricola::sole();

    expect($atividade->implementos->pluck('id')->all())->toBe([$this->cenario['grade']->id])
        ->and($atividade->custo_total)->toBe(1335.5);
});

test('mudar o preço dos recursos no cadastro não altera atividades já registradas', function () {
    $this->post(route('atividades.store'), dadosDaAtividade($this->cenario))->assertSessionHasNoErrors();

    $this->put(route('insumos.update', $this->cenario['ureia']), [
        'nome' => 'Ureia', 'categoria' => 'fertilizante', 'unidade' => 'kg',
        'valor_unitario' => 4.00, 'quantidade_estoque' => 0, 'status' => 'ativo',
    ])->assertSessionHasNoErrors();
    $this->put(route('maquinas.update', $this->cenario['trator']), [
        'nome' => 'Trator 01', 'tipo' => 'Trator', 'custo_hora' => 220.00, 'status' => 'ativo',
    ])->assertSessionHasNoErrors();
    $this->put(route('funcionarios.update', $this->cenario['operador']), [
        'nome' => 'Operador', 'funcao' => 'Tratorista', 'custo_hora' => 35.00, 'status' => 'ativo',
    ])->assertSessionHasNoErrors();

    $atividade = AtividadeAgricola::sole();

    expect($atividade->custo_total)->toBe(1335.5);
    $this->assertDatabaseHas('atividade_insumo', ['insumo_id' => $this->cenario['ureia']->id, 'custo_unitario' => 3.20, 'custo_total' => 800.00]);
    $this->assertDatabaseHas('atividade_maquina', ['maquina_id' => $this->cenario['trator']->id, 'custo_hora' => 180.00, 'custo_total' => 450.00]);
    $this->assertDatabaseHas('atividade_funcionario', ['funcionario_id' => $this->cenario['operador']->id, 'custo_hora' => 28.50, 'custo_total' => 85.50]);
});

test('na edição, itens existentes recalculam com o valor histórico e itens novos usam o valor atual do cadastro', function () {
    $this->post(route('atividades.store'), dadosDaAtividade($this->cenario))->assertSessionHasNoErrors();
    $atividade = AtividadeAgricola::sole();

    // Os preços sobem depois do lançamento.
    $this->cenario['ureia']->update(['valor_unitario' => 4.00]);
    $this->cenario['calcario']->update(['valor_unitario' => 160.00]);
    $this->cenario['trator']->update(['custo_hora' => 220.00]);

    // Edição: aumenta a quantidade de ureia e as horas do trator, e adiciona calcário (item novo).
    $this->put(route('atividades.update', $atividade), dadosDaAtividade($this->cenario, [
        'insumos' => [
            ['insumo_id' => $this->cenario['ureia']->id, 'quantidade' => 300],
            ['insumo_id' => $this->cenario['calcario']->id, 'quantidade' => 2],
        ],
        'maquinas' => [['maquina_id' => $this->cenario['trator']->id, 'horas' => 3]],
    ]))->assertSessionHasNoErrors();

    // Ureia: 300 × R$ 3,20 (histórico) = R$ 960,00 — não usa o novo preço de R$ 4,00.
    $this->assertDatabaseHas('atividade_insumo', ['insumo_id' => $this->cenario['ureia']->id, 'quantidade' => 300, 'custo_unitario' => 3.20, 'custo_total' => 960.00]);
    // Calcário (novo na atividade): 2 × R$ 160,00 (valor atual) = R$ 320,00.
    $this->assertDatabaseHas('atividade_insumo', ['insumo_id' => $this->cenario['calcario']->id, 'quantidade' => 2, 'custo_unitario' => 160.00, 'custo_total' => 320.00]);
    // Trator: 3 h × R$ 180,00 (histórico) = R$ 540,00.
    $this->assertDatabaseHas('atividade_maquina', ['maquina_id' => $this->cenario['trator']->id, 'horas' => 3, 'custo_hora' => 180.00, 'custo_total' => 540.00]);

    expect($atividade->fresh()->custo_total)->toBe(1905.5); // 960 + 320 + 540 + 85,50
});

test('a atividade guarda a safra do talhão no lançamento e não muda quando o talhão muda de safra', function () {
    $this->post(route('atividades.store'), dadosDaAtividade($this->cenario))->assertSessionHasNoErrors();

    $this->put(route('talhoes.update', $this->cenario['talhao']), [
        'nome' => 'Talhão 01', 'area' => 12.5, 'cultura' => 'Milho', 'safra' => '2026/2027', 'status' => 'em_cultivo',
    ])->assertSessionHasNoErrors();

    $this->post(route('atividades.store'), dadosDaAtividade($this->cenario, ['data' => '2026-09-01']))->assertSessionHasNoErrors();

    expect(AtividadeAgricola::query()->orderBy('id')->pluck('safra')->all())->toBe(['2025/2026', '2026/2027']);
});

test('editar a atividade sem trocar o talhão mantém a safra registrada', function () {
    $this->post(route('atividades.store'), dadosDaAtividade($this->cenario))->assertSessionHasNoErrors();
    $atividade = AtividadeAgricola::sole();

    $this->cenario['talhao']->update(['safra' => '2026/2027']);

    $this->put(route('atividades.update', $atividade), dadosDaAtividade($this->cenario, ['descricao' => 'Descrição corrigida']))
        ->assertSessionHasNoErrors();

    expect($atividade->fresh()->safra)->toBe('2025/2026');
});

test('excluir a atividade remove suas associações e mantém os recursos cadastrados', function () {
    $this->post(route('atividades.store'), dadosDaAtividade($this->cenario))->assertSessionHasNoErrors();
    $atividade = AtividadeAgricola::sole();

    $this->delete(route('atividades.destroy', $atividade))
        ->assertRedirect(route('atividades.index'))
        ->assertSessionHas('sucesso', 'Atividade excluída com sucesso.');

    $this->assertDatabaseCount('atividades_agricolas', 0);
    $this->assertDatabaseCount('atividade_insumo', 0);
    $this->assertDatabaseCount('atividade_maquina', 0);
    $this->assertDatabaseCount('atividade_funcionario', 0);
    $this->assertDatabaseCount('atividade_implemento', 0);
    $this->assertModelExists($this->cenario['ureia']);
});
