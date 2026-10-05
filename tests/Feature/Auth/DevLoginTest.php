<?php

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;

/*
 * O login rápido só pode funcionar com APP_ENV=local e DEV_LOGIN=true.
 * No servidor usado pelos estudantes ele precisa responder 404.
 *
 * Trocar o ambiente nos testes reativa a verificação de CSRF, que não é o
 * assunto destes testes; por isso ela é desligada aqui.
 */

beforeEach(function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
});

function habilitarLoginRapido(string $ambiente = 'local'): void
{
    app()->detectEnvironment(fn () => $ambiente);
    config(['app.dev_login' => true]);
}

test('dev login is disabled by default', function () {
    $user = User::factory()->create();

    $this->post(route('dev.login'), ['user_id' => $user->id])->assertNotFound();

    $this->assertGuest();
    $this->get(route('login'))->assertOk()->assertDontSee('Login rápido de desenvolvimento');
});

test('dev login stays disabled outside the local environment even with the flag on', function (string $ambiente) {
    habilitarLoginRapido($ambiente);
    $user = User::factory()->create();

    $this->post(route('dev.login'), ['user_id' => $user->id])->assertNotFound();

    $this->assertGuest();
    $this->get(route('login'))->assertDontSee('Login rápido de desenvolvimento');
})->with(['production', 'staging', 'testing']);

test('dev login stays disabled in the local environment when the flag is off', function () {
    app()->detectEnvironment(fn () => 'local');
    config(['app.dev_login' => false]);
    $user = User::factory()->create();

    $this->post(route('dev.login'), ['user_id' => $user->id])->assertNotFound();

    $this->assertGuest();
});

test('dev login signs in as the chosen user without a password', function () {
    habilitarLoginRapido();
    $user = User::factory()->create();

    $this->post(route('dev.login'), ['user_id' => $user->id])
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
});

test('dev login rejects a user that does not exist', function () {
    habilitarLoginRapido();

    $this->post(route('dev.login'), ['user_id' => 999])->assertSessionHasErrors('user_id');

    $this->assertGuest();
});

test('login screen lists the users for dev login when it is enabled', function () {
    habilitarLoginRapido();
    User::factory()->create(['name' => 'Aluno 01', 'email' => 'aluno01@exemplo.com']);

    $this->get(route('login'))
        ->assertOk()
        ->assertSee('Login rápido de desenvolvimento')
        ->assertSee('Aluno 01 (aluno01@exemplo.com)');
});
