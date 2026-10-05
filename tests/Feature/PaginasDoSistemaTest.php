<?php

use App\Models\User;
use App\Services\DadosDemonstracaoService;

/*
 * Todas as telas do sistema abrem sem erro com os dados de demonstração.
 */

beforeEach(function () {
    $this->usuario = User::factory()->create();
    $this->propriedade = app(DadosDemonstracaoService::class)->gerar($this->usuario);
    $this->actingAs($this->usuario);
});

test('as telas de listagem, cadastro e análise abrem com os dados de demonstração', function (string $rota) {
    $this->get(route($rota))->assertOk();
})->with([
    'dashboard',
    'propriedades.index', 'propriedades.create',
    'talhoes.index', 'talhoes.create',
    'funcionarios.index', 'funcionarios.create',
    'maquinas.index', 'maquinas.create',
    'implementos.index', 'implementos.create',
    'insumos.index', 'insumos.create',
    'atividades.index', 'atividades.create',
    'custos-talhoes.index',
    'relatorios.index',
]);

test('as telas de detalhes e edição abrem com os dados de demonstração', function () {
    $talhao = $this->propriedade->talhoes()->firstOrFail();
    $atividade = $this->propriedade->atividades()->firstOrFail();

    $this->get(route('propriedades.show', $this->propriedade))->assertOk()->assertSee('Talhão 01');
    $this->get(route('propriedades.edit', $this->propriedade))->assertOk();
    $this->get(route('talhoes.show', $talhao))->assertOk();
    $this->get(route('talhoes.edit', $talhao))->assertOk();
    $this->get(route('funcionarios.edit', $this->propriedade->funcionarios()->firstOrFail()))->assertOk();
    $this->get(route('maquinas.edit', $this->propriedade->maquinas()->firstOrFail()))->assertOk();
    $this->get(route('implementos.edit', $this->propriedade->implementos()->firstOrFail()))->assertOk();
    $this->get(route('insumos.edit', $this->propriedade->insumos()->firstOrFail()))->assertOk();
    $this->get(route('atividades.show', $atividade))->assertOk();
    $this->get(route('atividades.edit', $atividade))->assertOk();
});

test('o dashboard mostra os indicadores e os dados dos gráficos', function () {
    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee(['Área total', '50,00 ha', 'Composição dos custos', 'Custo por hectare dos talhões', 'Últimas atividades'])
        ->assertViewHas('quantidadeTalhoes', 5)
        ->assertViewHas('graficoCustoPorHectare', fn (array $grafico) => count($grafico['rotulos']) === 5);
});

test('usuário sem propriedade é orientado a cadastrar uma antes dos demais cadastros', function (string $rota) {
    $this->actingAs(User::factory()->create())
        ->get(route($rota))
        ->assertRedirect(route('propriedades.create'))
        ->assertSessionHas('erro');
})->with(['talhoes.create', 'funcionarios.create', 'maquinas.create', 'implementos.create', 'insumos.create', 'atividades.create']);

test('o cadastro de atividade orienta a cadastrar um talhão quando a propriedade não tem talhões', function () {
    $usuario = User::factory()->create();
    $propriedade = $usuario->propriedades()->create([
        'nome' => 'Sítio sem talhões', 'proprietario' => 'Fulano', 'municipio' => 'Bebedouro', 'uf' => 'SP', 'area_total' => 10,
    ]);

    $this->actingAs($usuario)
        ->get(route('atividades.create'))
        ->assertRedirect(route('talhoes.create', ['propriedade_id' => $propriedade->id]));
});
