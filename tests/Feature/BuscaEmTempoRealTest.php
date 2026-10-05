<?php

use App\Enums\TipoOperacao;
use App\Models\AtividadeAgricola;
use App\Models\Funcionario;
use App\Models\Implemento;
use App\Models\Insumo;
use App\Models\Maquina;
use App\Models\Propriedade;
use App\Models\Talhao;
use App\Models\User;

beforeEach(function () {
    $this->usuario = User::factory()->create();
    $this->propriedade = Propriedade::factory()->for($this->usuario)->create(['nome' => 'Fazenda Boa Vista', 'municipio' => 'Bebedouro']);
    $this->actingAs($this->usuario);
});

test('a busca filtra os registros de cada listagem pelo termo digitado', function (string $modelo, string $rota, string $campo, string $encontrado, string $outro, string $termo) {
    $modelo::factory()->for($this->propriedade)->create([$campo => $encontrado]);
    $modelo::factory()->for($this->propriedade)->create([$campo => $outro]);

    $this->get(route($rota, ['busca' => $termo]))
        ->assertOk()
        ->assertSee($encontrado)
        ->assertDontSee($outro);
})->with([
    'talhões pela cultura' => [Talhao::class, 'talhoes.index', 'cultura', 'Amendoim', 'Laranja', 'amend'],
    'funcionários pela função' => [Funcionario::class, 'funcionarios.index', 'funcao', 'Tratorista', 'Encarregado', 'trator'],
    'máquinas pelo nome' => [Maquina::class, 'maquinas.index', 'nome', 'Colhedora S540', 'Trator 6110J', 'colhedora'],
    'implementos pelo nome' => [Implemento::class, 'implementos.index', 'nome', 'Plantadeira 9 linhas', 'Grade aradora', 'plantad'],
    'insumos pelo nome' => [Insumo::class, 'insumos.index', 'nome', 'Glifosato', 'Ureia', 'glifo'],
]);

test('a busca de propriedades procura no nome, no proprietário e no município', function () {
    Propriedade::factory()->for($this->usuario)->create(['nome' => 'Sítio Esperança', 'municipio' => 'Jaboticabal']);

    $this->get(route('propriedades.index', ['busca' => 'bebedouro']))
        ->assertSee('Fazenda Boa Vista')
        ->assertDontSee('Sítio Esperança');
});

test('a busca de atividades encontra pelo nome da operação, mesmo sem acento, e pelo talhão', function () {
    $talhao = Talhao::factory()->for($this->propriedade)->create(['nome' => 'Talhão Norte']);
    $outroTalhao = Talhao::factory()->for($this->propriedade)->create(['nome' => 'Talhão Sul']);
    AtividadeAgricola::factory()->for($talhao)->create(['tipo' => TipoOperacao::Pulverizacao]);
    AtividadeAgricola::factory()->for($outroTalhao)->create(['tipo' => TipoOperacao::Colheita]);

    $this->get(route('atividades.index', ['busca' => 'pulverizacao']))
        ->assertViewHas('atividades', fn ($atividades) => $atividades->total() === 1 && $atividades->first()->tipo === TipoOperacao::Pulverizacao);

    $this->get(route('atividades.index', ['busca' => 'sul']))
        ->assertViewHas('atividades', fn ($atividades) => $atividades->total() === 1 && $atividades->first()->tipo === TipoOperacao::Colheita);
});

test('a busca em tempo real devolve apenas o trecho da lista, sem o layout da página', function () {
    Talhao::factory()->for($this->propriedade)->create(['nome' => 'Talhão Norte']);

    $resposta = $this->get(route('talhoes.index', ['busca' => 'norte']), ['X-Busca-Em-Tempo-Real' => '1']);

    $resposta->assertOk()
        ->assertSee('Talhão Norte')
        ->assertDontSee('<html', escape: false)
        ->assertDontSee('Novo talhão');
});

test('mostra uma mensagem quando a busca não encontra nada', function () {
    Talhao::factory()->for($this->propriedade)->create(['nome' => 'Talhão Norte']);

    $this->get(route('talhoes.index', ['busca' => 'inexistente']))
        ->assertOk()
        ->assertSee('Nenhum resultado encontrado')
        ->assertSee('Limpar busca e filtros');
});

test('a busca nunca traz registros de outro usuário', function () {
    $outraPropriedade = Propriedade::factory()->create();
    Talhao::factory()->for($outraPropriedade)->create(['nome' => 'Talhão do vizinho']);
    Talhao::factory()->for($this->propriedade)->create(['nome' => 'Meu talhão']);

    $this->get(route('talhoes.index', ['busca' => 'talhão']), ['X-Busca-Em-Tempo-Real' => '1'])
        ->assertSee('Meu talhão')
        ->assertDontSee('Talhão do vizinho');
});
