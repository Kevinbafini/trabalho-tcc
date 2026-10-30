<?php

use App\Livewire\Settings\DadosDemonstracao;
use App\Models\AtividadeAgricola;
use App\Models\Insumo;
use App\Models\Propriedade;
use App\Models\Talhao;
use App\Models\User;
use App\Services\DadosDemonstracaoService;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

test('gera uma propriedade de demonstração completa para o usuário', function () {
    $usuario = User::factory()->create();

    $propriedade = app(DadosDemonstracaoService::class)->gerar($usuario);

    expect($propriedade->user_id)->toBe($usuario->id)
        ->and($propriedade->area_total)->toBe(50.0)
        ->and($propriedade->talhoes()->count())->toBe(5)
        ->and($propriedade->talhoes()->distinct()->count('cultura'))->toBe(3)
        ->and($propriedade->funcionarios()->count())->toBe(5)
        ->and($propriedade->maquinas()->count())->toBe(4)
        ->and($propriedade->insumos()->count())->toBe(18)
        ->and($propriedade->atividades()->count())->toBeGreaterThanOrEqual(90)
        ->and($propriedade->areaOcupada())->toBeLessThanOrEqual($propriedade->area_total);
});

test('os custos das atividades de demonstração somam o total gravado em cada atividade', function () {
    $propriedade = app(DadosDemonstracaoService::class)->gerar(User::factory()->create());

    $propriedade->atividades()->each(function (AtividadeAgricola $atividade) {
        expect(round($atividade->custo_insumos + $atividade->custo_maquinas + $atividade->custo_mao_obra, 2))
            ->toBe($atividade->custo_total);
    });
});

test('cada usuário recebe dados independentes', function () {
    $servico = app(DadosDemonstracaoService::class);
    $primeira = $servico->gerar(User::factory()->create());
    $segunda = $servico->gerar(User::factory()->create());

    expect($primeira->id)->not->toBe($segunda->id)
        ->and($segunda->atividades()->count())->toBe($primeira->atividades()->count())
        ->and(AtividadeAgricola::query()->where('propriedade_id', $segunda->id)->whereIn('talhao_id', $primeira->talhoes()->select('id'))->exists())->toBeFalse();
});

test('o comando cria o usuário com os dados de demonstração e não duplica na segunda execução', function () {
    $this->artisan('demo:gerar', ['email' => 'aluno01@exemplo.com', '--nome' => 'Aluno 01', '--senha' => 'senha-de-teste'])
        ->assertSuccessful();

    $usuario = User::query()->where('email', 'aluno01@exemplo.com')->sole();

    $this->artisan('demo:gerar', ['email' => 'aluno01@exemplo.com'])
        ->expectsOutputToContain('já possui os dados de demonstração')
        ->assertSuccessful();

    expect($usuario->name)->toBe('Aluno 01')
        ->and(Propriedade::query()->whereBelongsTo($usuario)->count())->toBe(2);

    $this->post(route('login.store'), ['email' => 'aluno01@exemplo.com', 'password' => 'senha-de-teste'])
        ->assertRedirect(route('dashboard', absolute: false));
});

test('ativar cria a fazenda e o sítio marcados como demonstração, sem duplicar', function () {
    $usuario = User::factory()->create();
    $servico = app(DadosDemonstracaoService::class);

    expect($servico->ativo($usuario))->toBeFalse()
        ->and($servico->ativar($usuario))->toBeTrue()
        ->and($servico->ativar($usuario))->toBeFalse();

    $propriedades = $usuario->propriedades()->orderBy('nome')->get();
    $sitio = $propriedades->firstWhere('nome', DadosDemonstracaoService::NOME_DO_SITIO);

    expect($propriedades->pluck('nome')->all())->toBe([DadosDemonstracaoService::NOME_DA_PROPRIEDADE, DadosDemonstracaoService::NOME_DO_SITIO])
        ->and($propriedades->every(fn (Propriedade $propriedade) => $propriedade->demonstracao))->toBeTrue()
        ->and($sitio->talhoes()->pluck('cultura')->unique()->sort()->values()->all())->toBe(['Laranja', 'Limão'])
        ->and($sitio->atividades()->count())->toBe(36)
        ->and($servico->resumo($usuario))->toMatchArray(['propriedades' => 2, 'talhoes' => 8]);
});

test('remover apaga só os dados de demonstração e mantém os cadastros do usuário', function () {
    $cenario = criarPropriedadeComRecursos();
    $usuario = $cenario['usuario'];
    $this->actingAs($usuario)->post(route('atividades.store'), dadosDaAtividade($cenario))->assertSessionHasNoErrors();

    $servico = app(DadosDemonstracaoService::class);
    $servico->ativar($usuario);

    expect($servico->remover($usuario))->toBe(2)
        ->and($servico->ativo($usuario))->toBeFalse()
        ->and($usuario->propriedades()->pluck('id')->all())->toBe([$cenario['propriedade']->id])
        ->and(AtividadeAgricola::query()->pluck('propriedade_id')->unique()->all())->toBe([$cenario['propriedade']->id])
        ->and(Talhao::count())->toBe(1)
        ->and(Insumo::count())->toBe(2);
});

test('mesmo renomeada, a propriedade de demonstração é reconhecida pela marcação, e a de outro usuário fica intacta', function () {
    $servico = app(DadosDemonstracaoService::class);
    $usuario = User::factory()->create();
    $outro = User::factory()->create();
    $servico->ativar($usuario);
    $servico->ativar($outro);

    $usuario->propriedades()->where('nome', DadosDemonstracaoService::NOME_DA_PROPRIEDADE)->update(['nome' => 'Minha fazenda de testes']);

    $servico->remover($usuario);

    expect($usuario->propriedades()->count())->toBe(0)
        ->and($servico->resumo($outro))->toMatchArray(['propriedades' => 2, 'talhoes' => 8]);
});

test('o usuário ativa e remove os dados de demonstração pelas configurações', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    $this->get(route('dashboard'))->assertSee('Explorar com dados de demonstração');
    $this->get(route('dados-demonstracao.edit'))->assertOk()->assertSee(['Dados de demonstração desativados', 'Fazenda Santa Helena', 'Sítio Boa Vista']);

    Livewire::test(DadosDemonstracao::class)
        ->call('ativar')
        ->assertSee('Dados de demonstração ativos');

    expect($usuario->propriedades()->where('demonstracao', true)->count())->toBe(2);

    Livewire::test(DadosDemonstracao::class)
        ->call('remover')
        ->assertSee('Dados de demonstração desativados');

    expect($usuario->propriedades()->count())->toBe(0);
});

test('o seeder cria o administrador com os dados de demonstração e pode rodar de novo sem duplicar', function () {
    $this->seed();
    $this->seed();

    $administrador = User::query()->sole();

    expect($administrador->email)->toBe('admin@admin.com')
        ->and(Hash::check('password', $administrador->password))->toBeTrue()
        ->and($administrador->propriedades()->where('demonstracao', true)->count())->toBe(2);
});

test('em produção o seeder não usa a senha conhecida', function () {
    $this->app['env'] = 'production';

    // Em produção o db:seed só roda com --force, como no deploy.
    $this->artisan('db:seed', ['--force' => true])->assertSuccessful();

    expect(Hash::check('password', User::query()->sole()->password))->toBeFalse();
});
