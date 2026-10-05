<?php

use App\Enums\StatusTalhao;
use App\Models\Propriedade;
use App\Models\Talhao;
use App\Models\User;

beforeEach(function () {
    $this->usuario = User::factory()->create();
    $this->propriedade = Propriedade::factory()->for($this->usuario)->create(['area_total' => 50]);
    $this->talhao = Talhao::factory()->for($this->propriedade)->create(['nome' => 'Talhão 01', 'area' => 30]);
    Talhao::factory()->for($this->propriedade)->create(['nome' => 'Talhão 02', 'area' => 15]);

    $this->actingAs($this->usuario);
});

/**
 * @param  array<string, mixed>  $alteracoes
 * @return array<string, mixed>
 */
function dadosDoTalhao(array $alteracoes = []): array
{
    return [
        'nome' => 'Talhão novo',
        'area' => 10,
        'cultura' => 'Soja',
        'safra' => '2025/2026',
        'status' => 'em_cultivo',
        ...$alteracoes,
    ];
}

test('rejeita talhão que faria a soma das áreas ultrapassar a área total da propriedade', function () {
    $this->post(route('talhoes.store'), dadosDoTalhao(['propriedade_id' => $this->propriedade->id, 'area' => 10]))
        ->assertSessionHasErrors(['area' => 'A área informada ultrapassa a área disponível da propriedade. Área total: 50,00 ha · ocupada por talhões ativos: 45,00 ha · disponível: 5,00 ha.']);

    expect($this->propriedade->talhoes()->count())->toBe(2);
});

test('aceita talhão que ocupa exatamente a área disponível', function () {
    $this->post(route('talhoes.store'), dadosDoTalhao(['propriedade_id' => $this->propriedade->id, 'area' => 5]))
        ->assertSessionHasNoErrors();

    expect($this->propriedade->areaOcupada())->toBe(50.0);
});

test('talhões inativos não ocupam área da propriedade', function () {
    $this->post(route('talhoes.store'), dadosDoTalhao(['propriedade_id' => $this->propriedade->id, 'area' => 20, 'status' => 'inativo']))
        ->assertSessionHasNoErrors();

    $this->talhao->update(['status' => StatusTalhao::Inativo]);

    // Com o Talhão 01 (30 ha) inativo, há 35 ha disponíveis.
    $this->post(route('talhoes.store'), dadosDoTalhao(['propriedade_id' => $this->propriedade->id, 'nome' => 'Talhão 04', 'area' => 35]))
        ->assertSessionHasNoErrors();
});

test('na edição, a área do próprio talhão não é contada em dobro', function () {
    $this->put(route('talhoes.update', $this->talhao), dadosDoTalhao(['nome' => 'Talhão 01', 'area' => 35]))
        ->assertSessionHasNoErrors();

    $this->put(route('talhoes.update', $this->talhao), dadosDoTalhao(['nome' => 'Talhão 01', 'area' => 36]))
        ->assertSessionHasErrors('area');

    expect($this->talhao->fresh()->area)->toBe(35.0);
});

test('a área total da propriedade não pode ficar menor que a soma dos talhões ativos', function () {
    $this->put(route('propriedades.update', $this->propriedade), [
        'nome' => $this->propriedade->nome,
        'proprietario' => $this->propriedade->proprietario,
        'municipio' => $this->propriedade->municipio,
        'uf' => 'SP',
        'area_total' => 40,
    ])->assertSessionHasErrors(['area_total' => 'A área total não pode ser menor que a soma das áreas dos talhões ativos desta propriedade (45,00 ha).']);

    expect($this->propriedade->fresh()->area_total)->toBe(50.0);
});

test('rejeita nome de talhão repetido na mesma propriedade', function () {
    $this->post(route('talhoes.store'), dadosDoTalhao(['propriedade_id' => $this->propriedade->id, 'nome' => 'Talhão 01', 'area' => 1]))
        ->assertSessionHasErrors(['nome' => 'Já existe um talhão com este nome nesta propriedade.']);
});

test('exige a safra no formato ano/ano', function () {
    $this->post(route('talhoes.store'), dadosDoTalhao(['propriedade_id' => $this->propriedade->id, 'safra' => '2025', 'area' => 1]))
        ->assertSessionHasErrors(['safra' => 'Informe a safra no formato ano/ano, por exemplo: 2025/2026.']);
});
