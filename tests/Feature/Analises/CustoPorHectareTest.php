<?php

use App\Models\Talhao;

beforeEach(function () {
    $this->cenario = criarPropriedadeComRecursos();
    $this->actingAs($this->cenario['usuario']);

    // Duas atividades no Talhão 01 (12,5 ha): R$ 1.335,50 + R$ 1.335,50 = R$ 2.671,00.
    $this->post(route('atividades.store'), dadosDaAtividade($this->cenario))->assertSessionHasNoErrors();
    $this->post(route('atividades.store'), dadosDaAtividade($this->cenario, ['data' => '2026-04-10']))->assertSessionHasNoErrors();
});

test('calcula o custo por hectare do talhão dividindo o custo total pela área', function () {
    $this->get(route('talhoes.show', $this->cenario['talhao']))
        ->assertOk()
        ->assertViewHas('resumo', fn (array $resumo) => $resumo['custo_total'] === 2671.0
            && $resumo['area'] === 12.5
            && $resumo['custo_por_hectare'] === 213.68)
        ->assertSee('213,68');
});

test('mostra os custos acumulados de cada talhão, inclusive dos que não têm atividades', function () {
    $talhaoSemAtividades = Talhao::factory()->for($this->cenario['propriedade'])->create(['nome' => 'Talhão 02', 'area' => 10]);

    $this->get(route('custos-talhoes.index'))
        ->assertOk()
        ->assertViewHas('custosPorTalhao', function ($custos) use ($talhaoSemAtividades) {
            $talhao01 = $custos->firstWhere('talhao.id', $this->cenario['talhao']->id);
            $talhao02 = $custos->firstWhere('talhao.id', $talhaoSemAtividades->id);

            return $talhao01['quantidade_atividades'] === 2
                && $talhao01['custo_insumos'] === 1600.0
                && $talhao01['custo_maquinas'] === 900.0
                && $talhao01['custo_mao_obra'] === 171.0
                && $talhao01['custo_total'] === 2671.0
                && $talhao01['custo_por_hectare'] === 213.68
                && $talhao02['custo_total'] === 0.0
                && $talhao02['custo_por_hectare'] === 0.0;
        });
});

test('o custo médio por hectare do dashboard considera a área dos talhões com atividades', function () {
    $talhao02 = Talhao::factory()->for($this->cenario['propriedade'])->create(['nome' => 'Talhão 02', 'area' => 7.5]);
    $this->post(route('atividades.store'), dadosDaAtividade($this->cenario, ['talhao_id' => $talhao02->id]))->assertSessionHasNoErrors();

    // R$ 4.006,50 ÷ (12,5 ha + 7,5 ha) = R$ 200,33 por hectare.
    $this->get(route('dashboard'))
        ->assertOk()
        ->assertViewHas('resumo', fn (array $resumo) => $resumo['custo_total'] === 4006.5
            && $resumo['area'] === 20.0
            && $resumo['custo_por_hectare'] === 200.33)
        ->assertViewHas('graficoComposicao', fn (array $grafico) => $grafico['valores'] === [2400.0, 1350.0, 256.5]);
});

test('o relatório filtra por talhão, safra e período', function () {
    $this->cenario['talhao']->update(['safra' => '2026/2027']);
    $this->post(route('atividades.store'), dadosDaAtividade($this->cenario, ['data' => '2026-09-15']))->assertSessionHasNoErrors();

    $this->get(route('relatorios.index', ['safra' => '2025/2026']))
        ->assertViewHas('resumo', fn (array $resumo) => $resumo['quantidade_atividades'] === 2 && $resumo['custo_total'] === 2671.0);

    $this->get(route('relatorios.index', ['data_inicio' => '2026-04-01', 'data_fim' => '2026-09-30']))
        ->assertViewHas('resumo', fn (array $resumo) => $resumo['quantidade_atividades'] === 2);

    $this->get(route('relatorios.index', ['talhao_id' => $this->cenario['talhao']->id]))
        ->assertViewHas('resumo', fn (array $resumo) => $resumo['quantidade_atividades'] === 3 && $resumo['custo_por_hectare'] === 320.52);
});

test('o relatório rejeita período com data final anterior à inicial', function () {
    $this->get(route('relatorios.index', ['data_inicio' => '2026-05-01', 'data_fim' => '2026-04-01']))
        ->assertSessionHasErrors(['data_fim' => 'A data final deve ser igual ou posterior à data inicial.']);
});
