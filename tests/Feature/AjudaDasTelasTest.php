<?php

use App\Livewire\Settings\Profile;
use App\Models\User;
use Illuminate\Routing\Route;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route as Rotas;
use Livewire\Livewire;

/*
 * Botão "?" ao lado do título de cada tela, que abre uma janela explicando a
 * tela. Os textos ficam em lang/pt_BR/ajuda.php, pelo nome da rota.
 */

test('toda tela do sistema tem um texto de ajuda completo', function () {
    $telas = collect(Rotas::getRoutes()->getRoutes())
        ->filter(fn (Route $rota) => in_array('GET', $rota->methods()) && in_array('auth', $rota->gatherMiddleware()))
        ->map(fn (Route $rota) => $rota->getName())
        // O PDF é um download, o painel do assistente é um trecho carregado dentro de outras telas
        // e a autorização do OAuth é a tela de consentimento dos apps de IA externos.
        ->reject(fn (?string $nome) => $nome === null || in_array($nome, ['relatorios.pdf', 'assistente.painel', 'passport.authorizations.authorize'], true))
        ->values();

    $semAjuda = $telas->reject(fn (string $tela) => Arr::has((array) trans('ajuda.'.$tela), ['titulo', 'proposito', 'exibe', 'como_usar']));

    expect($telas)->toContain('dashboard', 'atividades.edit', 'profile.edit')
        ->and($semAjuda->all())->toBe([]);
});

test('o botão de ajuda explica a tela atual', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('propriedades.index'))
        ->assertOk()
        ->assertSee('Ajuda: o que é esta tela?')
        ->assertSeeText(['Para que serve', 'A propriedade é o ponto de partida do sistema', 'Como usar'])
        ->assertSee('<strong>Nova propriedade</strong>', false)
        ->assertDontSeeText('Cadastra uma propriedade rural'); // ajuda da tela de cadastro
});

test('as telas de edição mostram a ajuda da edição, não o título do registro', function () {
    $cenario = criarPropriedadeComRecursos();

    $this->actingAs($cenario['usuario'])
        ->get(route('talhoes.edit', $cenario['talhao']))
        ->assertOk()
        ->assertSeeText(['Editar talhão', 'Mudar a safra aqui não altera as atividades já lançadas.']);
});

test('as configurações mantêm a ajuda quando o Livewire atualiza a tela', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('profile.edit'))->assertSeeText('Mantém atualizados os dados da sua conta');

    // Nas atualizações do Livewire a rota atual não é a da tela, por isso a ajuda é informada pela própria tela.
    Livewire::test(Profile::class)
        ->set('name', 'Novo nome')
        ->call('updateProfileInformation')
        ->assertHasNoErrors()
        ->assertSeeText('Mantém atualizados os dados da sua conta');
});
