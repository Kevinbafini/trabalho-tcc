<?php

use App\Enums\TipoOperacao;
use App\Models\AtividadeAgricola;
use App\Models\Insumo;
use App\Models\Propriedade;
use App\Models\Talhao;
use App\Models\User;
use Laravel\Passport\Passport;

/*
 * CRUD de atividades agrícolas pela API (/api/v1/atividades), com os mesmos
 * cálculos de custo e validações da tela de lançamento. O cenário padrão
 * (criarPropriedadeComRecursos) gera uma atividade de R$ 1.335,50.
 */

beforeEach(function () {
    $this->cenario = criarPropriedadeComRecursos();
    Passport::actingAs($this->cenario['usuario'], ['api:leitura', 'api:escrita']);
});

test('lista só as atividades do usuário, das mais recentes para as mais antigas e paginadas', function () {
    AtividadeAgricola::factory()->create(['talhao_id' => $this->cenario['talhao']->id, 'data' => '2026-01-10', 'custo_total' => 200]);
    $recente = AtividadeAgricola::factory()->create(['talhao_id' => $this->cenario['talhao']->id, 'data' => '2026-02-20', 'custo_total' => 500]);
    AtividadeAgricola::factory()->create(['talhao_id' => criarPropriedadeComRecursos()['talhao']->id]);

    $resposta = $this->getJson(route('api.v1.atividades.index', ['por_pagina' => 1]));

    $resposta->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('meta.total', 2)
        ->assertJsonPath('data.0.id', $recente->id)
        ->assertJsonPath('data.0.custo_total', 500)
        ->assertJsonPath('data.0.talhao.nome', 'Talhão 01');
    expect($resposta->json('links.next'))->toContain('page=2');
});

test('filtra a listagem de atividades', function (Closure $filtro) {
    $outraPropriedade = Propriedade::factory()->for($this->cenario['usuario'])->create();
    $procurada = AtividadeAgricola::factory()->create([
        'talhao_id' => $this->cenario['talhao']->id,
        'tipo' => TipoOperacao::Plantio,
        'descricao' => 'Plantio de soja',
    ]);
    AtividadeAgricola::factory()->create([
        'talhao_id' => Talhao::factory()->for($outraPropriedade)->create()->id,
        'tipo' => TipoOperacao::Colheita,
        'descricao' => 'Colheita do milho',
    ]);

    $resposta = $this->getJson(route('api.v1.atividades.index', $filtro($this->cenario)));

    expect($resposta->assertOk()->json('data.*.id'))->toBe([$procurada->id]);
})->with([
    'por propriedade' => [fn (array $cenario): array => ['propriedade_id' => $cenario['propriedade']->id]],
    'por talhão' => [fn (array $cenario): array => ['talhao_id' => $cenario['talhao']->id]],
    'por tipo de operação' => [fn (array $cenario): array => ['tipo' => 'plantio']],
    'pela busca na descrição' => [fn (array $cenario): array => ['busca' => 'soja']],
]);

test('rejeita filtros inválidos na listagem', function () {
    $this->getJson(route('api.v1.atividades.index', ['tipo' => 'capina', 'por_pagina' => 500]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'tipo' => 'O valor selecionado em tipo de operação é inválido.',
            'por_pagina' => 'O campo itens por página deve estar entre 1 e 100.',
        ]);
});

test('cadastra a atividade, calcula os custos e responde 201 com o endereço do registro', function () {
    $resposta = $this->postJson(route('api.v1.atividades.store'), dadosDaAtividade($this->cenario));

    $atividade = AtividadeAgricola::sole();
    $resposta->assertCreated()
        ->assertHeader('Location', route('api.v1.atividades.show', $atividade))
        ->assertJsonPath('data.id', $atividade->id)
        ->assertJsonPath('data.safra', '2025/2026')
        ->assertJsonPath('data.custo_insumos', 800)
        ->assertJsonPath('data.custo_maquinas', 450)
        ->assertJsonPath('data.custo_mao_obra', 85.5)
        ->assertJsonPath('data.custo_total', 1335.5)
        ->assertJsonPath('data.insumos.0', [
            'insumo_id' => $this->cenario['ureia']->id,
            'nome' => 'Ureia',
            'unidade' => 'kg',
            'quantidade' => 250,
            'custo_unitario' => 3.2,
            'custo_total' => 800,
        ])
        ->assertJsonPath('data.implementos.0.implemento_id', $this->cenario['grade']->id);
    expect($atividade->custo_total)->toBe(1335.5)
        ->and($atividade->propriedade_id)->toBe($this->cenario['propriedade']->id);
});

test('ignora custos enviados na requisição: eles são sempre calculados pelo sistema', function () {
    $this->postJson(route('api.v1.atividades.store'), dadosDaAtividade($this->cenario, [
        'custo_total' => 1,
        'custo_insumos' => 1,
    ]))->assertCreated();

    expect(AtividadeAgricola::sole())
        ->custo_total->toBe(1335.5)
        ->custo_insumos->toBe(800.0);
});

test('valida o cadastro com as mesmas regras da tela de lançamento', function () {
    $this->postJson(route('api.v1.atividades.store'), [])
        ->assertUnprocessable()
        ->assertJsonPath('message', 'Os dados enviados são inválidos.')
        ->assertJsonValidationErrors([
            'propriedade_id' => 'O campo propriedade é obrigatório.',
            'talhao_id' => 'O campo talhão é obrigatório.',
            'data' => 'O campo data é obrigatório.',
            'tipo' => 'O campo tipo de operação é obrigatório.',
            'recursos' => 'Adicione pelo menos um insumo, uma máquina ou um funcionário para calcular o custo da atividade.',
        ]);

    $this->assertDatabaseCount('atividades_agricolas', 0);
});

test('rejeita recurso que pertence a outra propriedade', function () {
    $insumoDeOutraPropriedade = Insumo::factory()
        ->for(Propriedade::factory()->for($this->cenario['usuario']))
        ->create();

    $this->postJson(route('api.v1.atividades.store'), dadosDaAtividade($this->cenario, [
        'insumos' => [['insumo_id' => $insumoDeOutraPropriedade->id, 'quantidade' => 10]],
    ]))->assertJsonValidationErrors(['insumos.0.insumo_id' => 'O insumo selecionado não pertence a esta propriedade.']);

    $this->assertDatabaseCount('atividades_agricolas', 0);
});

test('não cadastra atividade em propriedade de outro usuário', function () {
    $outro = criarPropriedadeComRecursos();

    $this->postJson(route('api.v1.atividades.store'), dadosDaAtividade($outro))
        ->assertJsonValidationErrors(['propriedade_id' => 'O valor selecionado em propriedade é inválido.']);

    $this->assertDatabaseCount('atividades_agricolas', 0);
});

test('mostra a atividade com os valores gravados no lançamento, mesmo após mudar o preço do insumo', function () {
    $this->postJson(route('api.v1.atividades.store'), dadosDaAtividade($this->cenario))->assertCreated();
    $atividade = AtividadeAgricola::sole();
    $this->cenario['ureia']->update(['valor_unitario' => 9.99]);

    $resposta = $this->getJson(route('api.v1.atividades.show', $atividade));

    $resposta->assertOk()
        ->assertJsonPath('data.tipo', 'adubacao')
        ->assertJsonPath('data.tipo_rotulo', 'Adubação')
        ->assertJsonPath('data.insumos.0.custo_unitario', 3.2)
        ->assertJsonPath('data.maquinas.0.horas', 2.5)
        ->assertJsonPath('data.funcionarios.0.custo_total', 85.5)
        ->assertJsonPath('data.custo_total', 1335.5);
});

test('atualiza a atividade e recalcula os custos', function () {
    $this->postJson(route('api.v1.atividades.store'), dadosDaAtividade($this->cenario))->assertCreated();
    $atividade = AtividadeAgricola::sole();

    $resposta = $this->putJson(route('api.v1.atividades.update', $atividade), dadosDaAtividade($this->cenario, [
        'descricao' => 'Adubação corrigida',
        'maquinas' => [['maquina_id' => $this->cenario['trator']->id, 'horas' => 4]],
    ]));

    $resposta->assertOk()
        ->assertJsonPath('data.descricao', 'Adubação corrigida')
        ->assertJsonPath('data.custo_maquinas', 720)
        ->assertJsonPath('data.custo_total', 1605.5);
    expect($atividade->fresh())
        ->descricao->toBe('Adubação corrigida')
        ->custo_total->toBe(1605.5);
});

test('exclui a atividade e responde 204 sem conteúdo', function () {
    $this->postJson(route('api.v1.atividades.store'), dadosDaAtividade($this->cenario))->assertCreated();
    $atividade = AtividadeAgricola::sole();

    $this->deleteJson(route('api.v1.atividades.destroy', $atividade))->assertNoContent();

    $this->assertModelMissing($atividade);
    $this->assertDatabaseCount('atividade_insumo', 0);
});

test('a atividade de outro usuário responde 404 e não é alterada', function (string $metodo) {
    $this->postJson(route('api.v1.atividades.store'), dadosDaAtividade($this->cenario))->assertCreated();
    $atividadeDoDono = AtividadeAgricola::sole();
    Passport::actingAs(User::factory()->create(), ['api:leitura', 'api:escrita']);

    $this->json($metodo, route('api.v1.atividades.show', $atividadeDoDono), dadosDaAtividade($this->cenario, ['descricao' => 'Alterada']))
        ->assertNotFound()
        ->assertExactJson(['message' => 'Registro não encontrado.']);

    expect($atividadeDoDono->fresh()->descricao)->toBe('Adubação de cobertura');
})->with(['GET', 'PUT', 'DELETE']);
