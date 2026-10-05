<?php

use App\Livewire\Settings\DeleteUserForm;
use App\Models\AtividadeAgricola;
use App\Models\Propriedade;
use App\Models\User;
use Illuminate\Database\QueryException;
use Livewire\Livewire;

beforeEach(function () {
    $this->cenario = criarPropriedadeComRecursos();
    $this->actingAs($this->cenario['usuario']);
});

test('não permite excluir recurso já utilizado em atividade e orienta a inativá-lo', function (string $chave, string $rota, string $mensagem) {
    $this->post(route('atividades.store'), dadosDaAtividade($this->cenario))->assertSessionHasNoErrors();
    $recurso = $this->cenario[$chave];

    $this->from(route($rota.'.index'))
        ->delete(route($rota.'.destroy', $recurso))
        ->assertRedirect(route($rota.'.index'))
        ->assertSessionHas('erro', $mensagem);

    $this->assertModelExists($recurso);
})->with([
    'insumo' => ['ureia', 'insumos', 'O insumo "Ureia" já foi utilizado em atividades e não pode ser excluído, pois isso apagaria o histórico de custos. Para deixar de usá-lo, edite o cadastro e altere a situação para Inativo.'],
    'máquina' => ['trator', 'maquinas', 'A máquina "Trator 01" já foi utilizada em atividades e não pode ser excluída, pois isso apagaria o histórico de custos. Para deixar de usá-la, edite o cadastro e altere a situação para Inativo.'],
    'funcionário' => ['operador', 'funcionarios', 'O funcionário "Operador" já participou de atividades e não pode ser excluído, pois isso apagaria o histórico de custos. Para deixar de usá-lo, edite o cadastro e altere a situação para Inativo.'],
    'implemento' => ['grade', 'implementos', 'O implemento "Grade aradora" já foi utilizado em atividades e não pode ser excluído, pois isso apagaria o histórico de custos. Para deixar de usá-lo, edite o cadastro e altere a situação para Inativo.'],
]);

test('exclui recurso que ainda não foi utilizado em atividades', function (string $chave, string $rota) {
    $this->delete(route($rota.'.destroy', $this->cenario[$chave]))
        ->assertRedirect(route($rota.'.index'))
        ->assertSessionHas('sucesso');

    $this->assertModelMissing($this->cenario[$chave]);
})->with([
    'insumo' => ['ureia', 'insumos'],
    'máquina' => ['trator', 'maquinas'],
    'funcionário' => ['operador', 'funcionarios'],
    'implemento' => ['grade', 'implementos'],
]);

test('o banco de dados também impede apagar recurso com histórico', function () {
    $this->post(route('atividades.store'), dadosDaAtividade($this->cenario))->assertSessionHasNoErrors();

    expect(fn () => $this->cenario['ureia']->delete())->toThrow(QueryException::class);
});

test('não permite excluir talhão com atividades registradas', function () {
    $this->post(route('atividades.store'), dadosDaAtividade($this->cenario))->assertSessionHasNoErrors();

    $this->delete(route('talhoes.destroy', $this->cenario['talhao']))
        ->assertSessionHas('erro', 'O talhão "Talhão 01" possui atividades registradas e não pode ser excluído, pois isso apagaria o histórico de custos. Para deixar de usá-lo, edite o talhão e altere a situação para Inativo.');

    $this->assertModelExists($this->cenario['talhao']);
});

test('não permite excluir propriedade com dados vinculados, mas exclui propriedade vazia', function () {
    $this->delete(route('propriedades.destroy', $this->cenario['propriedade']))->assertSessionHas('erro');
    $this->assertModelExists($this->cenario['propriedade']);

    $propriedadeVazia = Propriedade::factory()->for($this->cenario['usuario'])->create();

    $this->delete(route('propriedades.destroy', $propriedadeVazia))
        ->assertRedirect(route('propriedades.index'))
        ->assertSessionHas('sucesso', 'Propriedade excluída com sucesso.');
    $this->assertModelMissing($propriedadeVazia);
});

test('o usuário pode excluir a própria conta mesmo com atividades registradas', function () {
    $this->post(route('atividades.store'), dadosDaAtividade($this->cenario))->assertSessionHasNoErrors();

    Livewire::test(DeleteUserForm::class)
        ->set('password', 'password')
        ->call('deleteUser')
        ->assertHasNoErrors();

    expect(User::count())->toBe(0)
        ->and(Propriedade::count())->toBe(0)
        ->and(AtividadeAgricola::count())->toBe(0);
    $this->assertDatabaseCount('insumos', 0);
});
