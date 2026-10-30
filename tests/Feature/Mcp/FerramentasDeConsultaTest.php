<?php

use App\Enums\StatusRecurso;
use App\Mcp\Servers\SistemaAgricolaServer;
use App\Mcp\Tools\BuscarCadastrosTool;
use App\Mcp\Tools\ConsultarCustosTool;
use App\Mcp\Tools\ListarAtividadesTool;
use App\Mcp\Tools\ResumoGeralTool;
use App\Models\Funcionario;

/*
 * Ferramentas de consulta do assistente de IA: somente leitura, sempre
 * restritas aos dados do usuário autenticado e com os mesmos números das telas.
 */

beforeEach(function () {
    $this->cenario = criarPropriedadeComRecursos();
    $this->outro = criarPropriedadeComRecursos();

    // Duas atividades de R$ 1.335,50 do usuário (adubação e pulverização) e uma do outro usuário.
    $this->actingAs($this->cenario['usuario']);
    $this->post(route('atividades.store'), dadosDaAtividade($this->cenario, ['data' => '2026-03-10', 'tipo' => 'adubacao']))->assertSessionHasNoErrors();
    $this->post(route('atividades.store'), dadosDaAtividade($this->cenario, ['data' => '2026-05-20', 'tipo' => 'pulverizacao']))->assertSessionHasNoErrors();

    $this->actingAs($this->outro['usuario']);
    $this->post(route('atividades.store'), dadosDaAtividade($this->outro))->assertSessionHasNoErrors();
});

test('o resumo geral mostra só as propriedades e os custos do próprio usuário', function () {
    SistemaAgricolaServer::actingAs($this->cenario['usuario'])
        ->tool(ResumoGeralTool::class)
        ->assertOk()
        ->assertSee([$this->cenario['propriedade']->nome, '"atividades_registradas":2', 'R$ 2.671,00', 'R$ 213,68', '2025/2026'])
        ->assertDontSee($this->outro['propriedade']->nome);
});

test('a busca devolve vários tipos de cadastro de uma vez, com IDs e sem os inativos', function () {
    Funcionario::factory()->for($this->cenario['propriedade'])->create(['nome' => 'Ex-funcionário', 'status' => StatusRecurso::Inativo]);

    SistemaAgricolaServer::actingAs($this->cenario['usuario'])
        ->tool(BuscarCadastrosTool::class, ['tipos' => ['talhoes', 'insumos', 'maquinas', 'funcionarios']])
        ->assertOk()
        ->assertSee([
            '"id":'.$this->cenario['talhao']->id, 'Talhão 01', 'Ureia', 'R$ 3,20', 'Trator 01', 'R$ 180,00', 'Operador', 'R$ 28,50',
        ])
        ->assertDontSee(['Ex-funcionário', '"id":'.$this->outro['ureia']->id.',']);

    SistemaAgricolaServer::actingAs($this->cenario['usuario'])
        ->tool(BuscarCadastrosTool::class, ['tipos' => ['funcionarios'], 'termo' => 'ex-func', 'incluir_inativos' => true])
        ->assertOk()
        ->assertSee(['Ex-funcionário', 'Inativo']);
});

test('a consulta de custos traz os mesmos números das telas de análise', function () {
    SistemaAgricolaServer::actingAs($this->cenario['usuario'])
        ->tool(ConsultarCustosTool::class, ['safra' => '2025/2026'])
        ->assertOk()
        ->assertSee([
            'R$ 1.600,00', // insumos: 2 × R$ 800,00
            'R$ 900,00',   // maquinário: 2 × R$ 450,00
            'R$ 171,00',   // mão de obra: 2 × R$ 85,50
            'R$ 2.671,00', // total
            'R$ 213,68',   // por hectare: R$ 2.671,00 ÷ 12,5 ha
            'Adubação', 'Pulverização', 'Mar/26', 'Soja',
        ]);
});

test('a consulta de custos não aceita filtro com dados de outro usuário', function () {
    SistemaAgricolaServer::actingAs($this->cenario['usuario'])
        ->tool(ConsultarCustosTool::class, ['propriedade_id' => $this->outro['propriedade']->id])
        ->assertHasErrors(['O valor selecionado em propriedade é inválido.']);
});

test('a lista de atividades filtra por operação e traz o link de cada atividade', function () {
    SistemaAgricolaServer::actingAs($this->cenario['usuario'])
        ->tool(ListarAtividadesTool::class, ['tipo' => 'pulverizacao'])
        ->assertOk()
        ->assertSee(['"total_encontrado":1', 'Pulverização', '20/05/2026', 'R$ 1.335,50', '/atividades/'])
        ->assertDontSee('10/03/2026'); // a adubação de março fica de fora
});
