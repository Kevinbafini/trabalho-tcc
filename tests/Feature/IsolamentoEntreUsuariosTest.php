<?php

use App\Models\AtividadeAgricola;
use App\Models\User;

/*
 * Cada usuário só enxerga e altera os dados das suas próprias propriedades.
 * Registros de outros usuários respondem 404, sem revelar que existem.
 */

beforeEach(function () {
    $this->dono = criarPropriedadeComRecursos();
    $this->actingAs($this->dono['usuario'])
        ->post(route('atividades.store'), dadosDaAtividade($this->dono))
        ->assertSessionHasNoErrors();
    $this->atividadeDoDono = AtividadeAgricola::sole();

    $this->outroUsuario = User::factory()->create();
    $this->actingAs($this->outroUsuario);
});

test('responde 404 ao abrir registros de outro usuário', function (string $rota, string $chave) {
    $registro = $chave === 'atividade' ? $this->atividadeDoDono : $this->dono[$chave];

    $this->get(route($rota, $registro))->assertNotFound();
})->with([
    'detalhes da propriedade' => ['propriedades.show', 'propriedade'],
    'edição da propriedade' => ['propriedades.edit', 'propriedade'],
    'detalhes do talhão' => ['talhoes.show', 'talhao'],
    'edição do talhão' => ['talhoes.edit', 'talhao'],
    'edição do funcionário' => ['funcionarios.edit', 'operador'],
    'edição da máquina' => ['maquinas.edit', 'trator'],
    'edição do implemento' => ['implementos.edit', 'grade'],
    'edição do insumo' => ['insumos.edit', 'ureia'],
    'detalhes da atividade' => ['atividades.show', 'atividade'],
    'edição da atividade' => ['atividades.edit', 'atividade'],
]);

test('não permite alterar registros de outro usuário', function () {
    $this->put(route('insumos.update', $this->dono['ureia']), [
        'nome' => 'Alterado', 'categoria' => 'fertilizante', 'unidade' => 'kg',
        'valor_unitario' => 1, 'quantidade_estoque' => 0, 'status' => 'ativo',
    ])->assertNotFound();

    $this->put(route('atividades.update', $this->atividadeDoDono), dadosDaAtividade($this->dono))->assertNotFound();

    expect($this->dono['ureia']->fresh()->nome)->toBe('Ureia')
        ->and($this->dono['ureia']->fresh()->valor_unitario)->toBe(3.2);
});

test('não permite excluir registros de outro usuário', function () {
    $this->delete(route('talhoes.destroy', $this->dono['talhao']))->assertNotFound();
    $this->delete(route('atividades.destroy', $this->atividadeDoDono))->assertNotFound();
    $this->delete(route('propriedades.destroy', $this->dono['propriedade']))->assertNotFound();

    $this->assertModelExists($this->dono['talhao']);
    $this->assertModelExists($this->atividadeDoDono);
    $this->assertModelExists($this->dono['propriedade']);
});

test('as listagens mostram apenas os registros do próprio usuário', function () {
    $meusDados = criarPropriedadeComRecursos($this->outroUsuario);
    $meusDados['talhao']->update(['nome' => 'Meu talhão']);
    $meusDados['propriedade']->update(['nome' => 'Minha fazenda']);
    $this->dono['talhao']->update(['nome' => 'Talhão do vizinho']);
    $this->dono['propriedade']->update(['nome' => 'Fazenda do vizinho']);

    $this->get(route('talhoes.index'))
        ->assertOk()
        ->assertSee('Meu talhão')
        ->assertDontSee('Talhão do vizinho');

    $this->get(route('propriedades.index'))
        ->assertOk()
        ->assertSee('Minha fazenda')
        ->assertDontSee('Fazenda do vizinho');
});

test('não permite cadastrar talhão em propriedade de outro usuário', function () {
    $this->post(route('talhoes.store'), [
        'propriedade_id' => $this->dono['propriedade']->id,
        'nome' => 'Invasor',
        'area' => 1,
        'cultura' => 'Soja',
        'safra' => '2025/2026',
        'status' => 'em_cultivo',
    ])->assertSessionHasErrors(['propriedade_id' => 'O valor selecionado em propriedade é inválido.']);

    expect($this->dono['propriedade']->talhoes()->count())->toBe(1);
});

test('não permite registrar atividade na propriedade ou com recursos de outro usuário', function () {
    $meusDados = criarPropriedadeComRecursos($this->outroUsuario);

    $this->post(route('atividades.store'), dadosDaAtividade($this->dono))
        ->assertSessionHasErrors(['propriedade_id']);

    $this->post(route('atividades.store'), dadosDaAtividade($meusDados, [
        'insumos' => [['insumo_id' => $this->dono['ureia']->id, 'quantidade' => 10]],
    ]))->assertSessionHasErrors(['insumos.0.insumo_id' => 'O insumo selecionado não pertence a esta propriedade.']);

    expect(AtividadeAgricola::count())->toBe(1);
});

test('o dashboard e os relatórios não somam custos de outros usuários', function () {
    criarPropriedadeComRecursos($this->outroUsuario);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertViewHas('resumo', fn (array $resumo) => $resumo['custo_total'] === 0.0 && $resumo['quantidade_atividades'] === 0);

    $this->get(route('relatorios.index'))
        ->assertOk()
        ->assertViewHas('resumo', fn (array $resumo) => $resumo['custo_total'] === 0.0);
});

test('os filtros de relatório rejeitam propriedade ou talhão de outro usuário', function () {
    $this->get(route('relatorios.index', ['propriedade_id' => $this->dono['propriedade']->id]))
        ->assertSessionHasErrors(['propriedade_id' => 'O valor selecionado em propriedade é inválido.']);

    $this->get(route('relatorios.index', ['talhao_id' => $this->dono['talhao']->id]))
        ->assertSessionHasErrors(['talhao_id' => 'O valor selecionado em talhão é inválido.']);
});
