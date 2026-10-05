<?php

use App\Models\AtividadeAgricola;
use App\Models\Propriedade;
use App\Models\User;
use App\Services\DadosDemonstracaoService;

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
        ->expectsOutputToContain('já possui a propriedade de demonstração')
        ->assertSuccessful();

    expect($usuario->name)->toBe('Aluno 01')
        ->and(Propriedade::query()->whereBelongsTo($usuario)->count())->toBe(1);

    $this->post(route('login.store'), ['email' => 'aluno01@exemplo.com', 'password' => 'senha-de-teste'])
        ->assertRedirect(route('dashboard', absolute: false));
});
