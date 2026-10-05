<?php

use App\Enums\TipoOperacao;
use App\Models\AtividadeAgricola;
use App\Models\Propriedade;
use App\Models\Talhao;
use App\Models\User;
use App\Services\GraficosCustoService;
use App\Services\IndicadoresCustoService;

beforeEach(function () {
    $this->cenario = criarPropriedadeComRecursos();
    $this->actingAs($this->cenario['usuario']);

    // Talhão 01 (soja, 12,5 ha): duas atividades de R$ 1.335,50 (março e maio).
    $this->post(route('atividades.store'), dadosDaAtividade($this->cenario, ['data' => '2026-03-10', 'tipo' => 'adubacao']))->assertSessionHasNoErrors();
    $this->post(route('atividades.store'), dadosDaAtividade($this->cenario, ['data' => '2026-05-20', 'tipo' => 'pulverizacao']))->assertSessionHasNoErrors();
});

test('agrupa os custos por mês e inclui os meses sem atividades', function () {
    $meses = app(IndicadoresCustoService::class)->custosPorMes($this->cenario['usuario']);

    expect(array_column($meses, 'rotulo'))->toBe(['Mar/26', 'Abr/26', 'Mai/26'])
        ->and(array_column($meses, 'custo_total'))->toBe([1335.5, 0.0, 1335.5])
        ->and($meses[0]['custo_insumos'])->toBe(800.0);
});

test('soma os custos por tipo de operação', function () {
    $this->post(route('atividades.store'), dadosDaAtividade($this->cenario, ['tipo' => 'pulverizacao', 'insumos' => [], 'maquinas' => []]))->assertSessionHasNoErrors();

    $operacoes = app(IndicadoresCustoService::class)->custosPorOperacao($this->cenario['usuario']);

    expect($operacoes[0]['tipo'])->toBe(TipoOperacao::Pulverizacao)
        ->and($operacoes[0]['quantidade_atividades'])->toBe(2)
        ->and($operacoes[0]['custo_total'])->toBe(1421.0) // 1.335,50 + 85,50
        ->and($operacoes[1]['tipo'])->toBe(TipoOperacao::Adubacao);
});

test('calcula o custo por hectare de cada cultura', function () {
    $milho = Talhao::factory()->for($this->cenario['propriedade'])->create(['nome' => 'Talhão 02', 'cultura' => 'Milho', 'area' => 10]);
    $this->post(route('atividades.store'), dadosDaAtividade($this->cenario, ['talhao_id' => $milho->id]))->assertSessionHasNoErrors();

    $indicadores = app(IndicadoresCustoService::class);
    $culturas = $indicadores->custosPorCultura($indicadores->custosPorTalhao($this->cenario['usuario']));

    // Soja: R$ 2.671,00 ÷ 12,5 ha = R$ 213,68/ha · Milho: R$ 1.335,50 ÷ 10 ha = R$ 133,55/ha
    expect(array_column($culturas, 'cultura'))->toBe(['Soja', 'Milho'])
        ->and(array_column($culturas, 'custo_por_hectare'))->toBe([213.68, 133.55]);
});

test('prepara barras e colunas do PDF proporcionais ao maior valor', function () {
    $graficos = app(GraficosCustoService::class);

    $barras = $graficos->barrasParaPdf(['rotulos' => ['A', 'B'], 'valores' => [200.0, 50.0]]);
    $colunas = $graficos->colunasParaPdf([
        'rotulos' => ['Jan', 'Fev'],
        'series' => [
            ['rotulo' => 'Insumos', 'valores' => [60.0, 30.0]],
            ['rotulo' => 'Maquinário', 'valores' => [30.0, 0.0]],
            ['rotulo' => 'Mão de obra', 'valores' => [10.0, 20.0]],
        ],
    ], alturaMaxima: 100);

    expect(array_column($barras, 'largura'))->toBe([100.0, 25.0])
        ->and($colunas[0]['alturas'])->toBe([60, 30, 10])
        ->and($colunas[1]['alturas'])->toBe([30, 0, 20]);
});

test('as telas de custos por talhão e de relatório exibem os gráficos', function () {
    $this->get(route('custos-talhoes.index'))
        ->assertOk()
        ->assertSee(['Custo total por talhão', 'Custo por hectare por cultura', 'Exportar PDF'])
        ->assertViewHas('graficoCustoPorCultura', fn (array $grafico) => $grafico['rotulos'] === ['Soja']);

    $this->get(route('relatorios.index'))
        ->assertOk()
        ->assertSee(['Evolução mensal dos custos', 'Custo por tipo de operação', 'Gerar relatório em PDF'])
        ->assertViewHas('graficoMensal', fn (array $grafico) => $grafico['rotulos'] === ['Mar/26', 'Abr/26', 'Mai/26']);
});

test('gera o relatório de custos em PDF', function () {
    $resposta = $this->get(route('relatorios.pdf', ['talhao_id' => $this->cenario['talhao']->id, 'safra' => '2025/2026']));

    $resposta->assertOk()->assertHeader('Content-Type', 'application/pdf');
    expect($resposta->headers->get('Content-Disposition'))->toContain('relatorio-de-custos-')
        ->and(substr((string) $resposta->getContent(), 0, 5))->toBe('%PDF-');
});

test('gera o PDF mesmo quando os filtros não encontram atividades', function () {
    $this->get(route('relatorios.pdf', ['data_inicio' => '2030-01-01']))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');
});

test('o PDF não aceita filtros com dados de outro usuário', function () {
    $outraPropriedade = Propriedade::factory()->for(User::factory())->create();

    $this->get(route('relatorios.pdf', ['propriedade_id' => $outraPropriedade->id]))
        ->assertSessionHasErrors(['propriedade_id' => 'O valor selecionado em propriedade é inválido.']);
});

test('o PDF de outro usuário não inclui as atividades deste usuário', function () {
    $this->actingAs(User::factory()->create());

    $indicadores = app(IndicadoresCustoService::class);

    expect($indicadores->custosPorMes(auth()->user()))->toBe([])
        ->and(AtividadeAgricola::count())->toBe(2);

    $this->get(route('relatorios.pdf'))->assertOk();
});
