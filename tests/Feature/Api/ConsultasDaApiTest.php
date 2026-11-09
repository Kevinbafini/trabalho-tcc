<?php

use App\Models\AtividadeAgricola;
use App\Models\Propriedade;
use App\Models\Talhao;
use Laravel\Passport\Passport;

/*
 * Consultas da API: propriedades, talhões, recursos (insumos, máquinas,
 * funcionários e implementos) e o relatório de custos. Tudo parte dos dados
 * do usuário do token; os de outro usuário nunca aparecem.
 *
 * Cenário padrão: talhão de 12,5 ha e uma atividade de R$ 1.335,50, ou seja,
 * R$ 106,84 por hectare.
 */

beforeEach(function () {
    $this->cenario = criarPropriedadeComRecursos();
    $this->outroUsuario = criarPropriedadeComRecursos();
    Passport::actingAs($this->cenario['usuario'], ['api:leitura', 'api:escrita']);
});

test('lista só as propriedades do usuário, com a quantidade de talhões', function () {
    $resposta = $this->getJson(route('api.v1.propriedades.index'));

    $resposta->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $this->cenario['propriedade']->id)
        ->assertJsonPath('data.0.quantidade_talhoes', 1);
});

test('mostra a propriedade com o resumo dos custos das atividades', function () {
    $this->postJson(route('api.v1.atividades.store'), dadosDaAtividade($this->cenario))->assertCreated();

    $resposta = $this->getJson(route('api.v1.propriedades.show', $this->cenario['propriedade']));

    $resposta->assertOk()
        ->assertJsonPath('data.nome', $this->cenario['propriedade']->nome)
        ->assertJsonPath('data.area_total', 50)
        ->assertJsonPath('custos.quantidade_atividades', 1)
        ->assertJsonPath('custos.custo_total', 1335.5);
});

test('lista só os talhões do usuário', function () {
    $outraPropriedade = Propriedade::factory()->for($this->cenario['usuario'])->create();
    $outroTalhao = Talhao::factory()->for($outraPropriedade)->create(['nome' => 'Talhão 02']);

    $resposta = $this->getJson(route('api.v1.talhoes.index'));

    expect($resposta->assertOk()->json('data.*.id'))->toBe([$this->cenario['talhao']->id, $outroTalhao->id]);
});

test('filtra os talhões por propriedade', function () {
    Talhao::factory()->for(Propriedade::factory()->for($this->cenario['usuario']))->create();

    $resposta = $this->getJson(route('api.v1.talhoes.index', ['propriedade_id' => $this->cenario['propriedade']->id]));

    expect($resposta->assertOk()->json('data.*.id'))->toBe([$this->cenario['talhao']->id]);
});

test('mostra o talhão com os custos e o custo por hectare', function () {
    $this->postJson(route('api.v1.atividades.store'), dadosDaAtividade($this->cenario))->assertCreated();

    $resposta = $this->getJson(route('api.v1.talhoes.show', $this->cenario['talhao']));

    $resposta->assertOk()
        ->assertJsonPath('data.nome', 'Talhão 01')
        ->assertJsonPath('data.area', 12.5)
        ->assertJsonPath('data.status', 'em_cultivo')
        ->assertJsonPath('data.status_rotulo', 'Em cultivo')
        ->assertJsonPath('data.propriedade.id', $this->cenario['propriedade']->id)
        ->assertJsonPath('custos.custo_total', 1335.5)
        ->assertJsonPath('custos.custo_por_hectare', 106.84);
});

test('propriedade e talhão de outro usuário respondem 404', function (string $rota, string $chave) {
    $this->getJson(route($rota, $this->outroUsuario[$chave]))
        ->assertNotFound()
        ->assertExactJson(['message' => 'Registro não encontrado.']);
})->with([
    'propriedade' => ['api.v1.propriedades.show', 'propriedade'],
    'talhão' => ['api.v1.talhoes.show', 'talhao'],
]);

test('lista só os recursos do usuário, em ordem alfabética', function (string $rota, array $chavesEsperadas) {
    $resposta = $this->getJson(route($rota));

    $idsEsperados = array_map(fn (string $chave): int => $this->cenario[$chave]->id, $chavesEsperadas);
    expect($resposta->assertOk()->json('data.*.id'))->toBe($idsEsperados);
})->with([
    'insumos' => ['api.v1.insumos.index', ['calcario', 'ureia']],
    'máquinas' => ['api.v1.maquinas.index', ['trator']],
    'funcionários' => ['api.v1.funcionarios.index', ['operador']],
    'implementos' => ['api.v1.implementos.index', ['grade']],
]);

test('busca insumos pelo nome e devolve os dados usados no cadastro de atividades', function () {
    $resposta = $this->getJson(route('api.v1.insumos.index', ['busca' => 'ure']));

    $resposta->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $this->cenario['ureia']->id)
        ->assertJsonPath('data.0.propriedade_id', $this->cenario['propriedade']->id)
        ->assertJsonPath('data.0.valor_unitario', 3.2)
        ->assertJsonPath('data.0.status', 'ativo');
});

test('o relatório traz os custos calculados no lançamento, só do usuário', function () {
    $this->postJson(route('api.v1.atividades.store'), dadosDaAtividade($this->cenario))->assertCreated();
    AtividadeAgricola::factory()->create(['talhao_id' => $this->outroUsuario['talhao']->id, 'custo_total' => 999]);

    $resposta = $this->getJson(route('api.v1.relatorios.custos'));

    $resposta->assertOk()
        ->assertJsonPath('data.resumo', [
            'quantidade_atividades' => 1,
            'custo_insumos' => 800,
            'custo_maquinas' => 450,
            'custo_mao_obra' => 85.5,
            'custo_total' => 1335.5,
            'area' => 12.5,
            'custo_por_hectare' => 106.84,
        ])
        ->assertJsonCount(1, 'data.por_talhao')
        ->assertJsonPath('data.por_talhao.0.talhao_id', $this->cenario['talhao']->id)
        ->assertJsonPath('data.por_talhao.0.custo_por_hectare', 106.84)
        ->assertJsonPath('data.por_operacao.0.tipo', 'adubacao')
        ->assertJsonPath('data.por_operacao.0.tipo_rotulo', 'Adubação')
        ->assertJsonPath('data.por_mes.0.mes', '2026-03')
        ->assertJsonPath('data.por_cultura.0.cultura', 'Soja');
});

test('o relatório aplica o filtro de período', function () {
    $this->postJson(route('api.v1.atividades.store'), dadosDaAtividade($this->cenario))->assertCreated();

    $resposta = $this->getJson(route('api.v1.relatorios.custos', ['data_inicio' => '2026-04-01']));

    $resposta->assertOk()
        ->assertJsonPath('data.filtros.data_inicio', '2026-04-01')
        ->assertJsonPath('data.resumo.custo_total', 0)
        ->assertJsonPath('data.por_talhao', []);
});

test('o relatório rejeita propriedade de outro usuário e período invertido', function () {
    $this->getJson(route('api.v1.relatorios.custos', [
        'propriedade_id' => $this->outroUsuario['propriedade']->id,
        'data_inicio' => '2026-05-01',
        'data_fim' => '2026-04-01',
    ]))->assertUnprocessable()->assertJsonValidationErrors([
        'propriedade_id' => 'O valor selecionado em propriedade é inválido.',
        'data_fim' => 'A data final deve ser igual ou posterior à data inicial.',
    ]);
});
