<?php

use App\Mcp\Servers\SistemaAgricolaServer;
use App\Mcp\Tools\CadastrarFuncionarioTool;
use App\Mcp\Tools\CadastrarImplementoTool;
use App\Mcp\Tools\CadastrarInsumoTool;
use App\Mcp\Tools\CadastrarMaquinaTool;
use App\Mcp\Tools\CadastrarPropriedadeTool;
use App\Mcp\Tools\CadastrarTalhaoTool;
use App\Mcp\Tools\RegistrarAtividadeTool;
use App\Models\AtividadeAgricola;
use App\Models\Funcionario;
use App\Models\Implemento;
use App\Models\Insumo;
use App\Models\Maquina;
use App\Models\Propriedade;
use App\Models\Talhao;
use App\Models\User;

/*
 * Ferramentas de cadastro do assistente de IA: primeiro devolvem uma prévia
 * (nada é gravado) e só gravam com confirmado_pelo_usuario=true, validando
 * com os mesmos Form Requests das telas.
 */

beforeEach(function () {
    $this->cenario = criarPropriedadeComRecursos();
});

test('a prévia da atividade calcula o custo item por item sem gravar nada', function () {
    SistemaAgricolaServer::actingAs($this->cenario['usuario'])
        ->tool(RegistrarAtividadeTool::class, [...dadosDaAtividade($this->cenario), 'confirmado_pelo_usuario' => false])
        ->assertOk()
        ->assertSee(['250', 'R$ 3,20', 'R$ 800,00', 'Trator 01: 2,5 h × R$ 180,00/h = R$ 450,00', 'R$ 85,50', 'R$ 1.335,50', 'Grade aradora', 'Nada foi salvo']);

    expect(AtividadeAgricola::count())->toBe(0);
});

test('com a confirmação registra a atividade com o mesmo cálculo e histórico das telas', function () {
    SistemaAgricolaServer::actingAs($this->cenario['usuario'])
        ->tool(RegistrarAtividadeTool::class, [...dadosDaAtividade($this->cenario), 'confirmado_pelo_usuario' => true])
        ->assertOk()
        ->assertSee(['"salvo":true', 'R$ 1.335,50']);

    $atividade = AtividadeAgricola::sole();

    expect($atividade->custo_total)->toBe(1335.5)
        ->and($atividade->safra)->toBe('2025/2026')
        ->and($atividade->insumos->sole()->pivot->custo_unitario)->toBe(3.2)
        ->and($atividade->implementos->pluck('nome')->all())->toBe(['Grade aradora']);
});

test('cada ferramenta de cadastro só grava depois da confirmação', function (string $ferramenta, string $modelo, array $campos) {
    $argumentos = $ferramenta === CadastrarPropriedadeTool::class
        ? $campos
        : ['propriedade_id' => $this->cenario['propriedade']->id, ...$campos];
    $quantidadeAntes = $modelo::count();

    SistemaAgricolaServer::actingAs($this->cenario['usuario'])
        ->tool($ferramenta, [...$argumentos, 'confirmado_pelo_usuario' => false])
        ->assertOk()
        ->assertSee(['Nada foi salvo', $campos['nome']]);

    expect($modelo::count())->toBe($quantidadeAntes);

    SistemaAgricolaServer::actingAs($this->cenario['usuario'])
        ->tool($ferramenta, [...$argumentos, 'confirmado_pelo_usuario' => true])
        ->assertOk()
        ->assertSee('"salvo":true');

    expect($modelo::count())->toBe($quantidadeAntes + 1)
        ->and($modelo::query()->latest('id')->value('nome'))->toBe($campos['nome']);
})->with([
    'propriedade' => [CadastrarPropriedadeTool::class, Propriedade::class, ['nome' => 'Sítio Boa Vista', 'proprietario' => 'Ana Souza', 'municipio' => 'Bebedouro', 'uf' => 'sp', 'area_total' => 20]],
    'talhão' => [CadastrarTalhaoTool::class, Talhao::class, ['nome' => 'Talhão 02', 'area' => 10, 'cultura' => 'Milho', 'safra' => '2025/2026']],
    'funcionário' => [CadastrarFuncionarioTool::class, Funcionario::class, ['nome' => 'João Lima', 'funcao' => 'Tratorista', 'custo_hora' => 30]],
    'máquina' => [CadastrarMaquinaTool::class, Maquina::class, ['nome' => 'Pulverizador 01', 'tipo' => 'Pulverizador', 'custo_hora' => 120]],
    'implemento' => [CadastrarImplementoTool::class, Implemento::class, ['nome' => 'Plantadeira 01', 'tipo' => 'Plantadeira']],
    'insumo' => [CadastrarInsumoTool::class, Insumo::class, ['nome' => 'Glifosato', 'categoria' => 'herbicida', 'unidade' => 'L', 'valor_unitario' => 25]],
]);

test('as regras das telas valem também para a IA', function () {
    // A soma das áreas dos talhões ativos (12,5 ha + 40 ha) passaria dos 50 ha da propriedade.
    SistemaAgricolaServer::actingAs($this->cenario['usuario'])
        ->tool(CadastrarTalhaoTool::class, [
            'confirmado_pelo_usuario' => true,
            'propriedade_id' => $this->cenario['propriedade']->id,
            'nome' => 'Talhão 02', 'area' => 40, 'cultura' => 'Milho', 'safra' => '2025/2026',
        ])
        ->assertHasErrors(['Dados inválidos']);

    SistemaAgricolaServer::actingAs($this->cenario['usuario'])
        ->tool(RegistrarAtividadeTool::class, [...dadosDaAtividade($this->cenario, ['data' => now()->addDay()->toDateString()]), 'confirmado_pelo_usuario' => true])
        ->assertHasErrors(['A data da atividade não pode ser uma data futura.']);

    expect(Talhao::count())->toBe(1)
        ->and(AtividadeAgricola::count())->toBe(0);
});

test('a IA não grava nada em propriedade ou com recursos de outro usuário', function () {
    $outro = criarPropriedadeComRecursos();

    SistemaAgricolaServer::actingAs($this->cenario['usuario'])
        ->tool(CadastrarTalhaoTool::class, [
            'confirmado_pelo_usuario' => true,
            'propriedade_id' => $outro['propriedade']->id,
            'nome' => 'Talhão invasor', 'area' => 1, 'cultura' => 'Soja', 'safra' => '2025/2026',
        ])
        ->assertHasErrors(['O valor selecionado em propriedade é inválido.']);

    SistemaAgricolaServer::actingAs($this->cenario['usuario'])
        ->tool(RegistrarAtividadeTool::class, [
            ...dadosDaAtividade($this->cenario, ['insumos' => [['insumo_id' => $outro['ureia']->id, 'quantidade' => 10]]]),
            'confirmado_pelo_usuario' => true,
        ])
        ->assertHasErrors(['O insumo selecionado não pertence a esta propriedade.']);

    expect(Talhao::query()->where('nome', 'Talhão invasor')->exists())->toBeFalse()
        ->and(AtividadeAgricola::count())->toBe(0);
});

test('sem usuário autenticado nenhuma ferramenta é executada', function () {
    SistemaAgricolaServer::tool(CadastrarPropriedadeTool::class, [
        'confirmado_pelo_usuario' => true,
        'nome' => 'Sem dono', 'proprietario' => 'Ninguém', 'municipio' => 'Bebedouro', 'uf' => 'SP', 'area_total' => 10,
    ])->assertHasErrors();

    expect(Propriedade::query()->where('nome', 'Sem dono')->exists())->toBeFalse()
        ->and(User::count())->toBe(1);
});
