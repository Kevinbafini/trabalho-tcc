<?php

use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;

/*
 * A verificação de e-mail está desativada: o estudante cria a conta e já
 * entra no sistema, sem depender do recebimento de e-mail (testes SUS).
 */

test('email verification routes are not registered', function () {
    expect(Route::has('verification.notice'))->toBeFalse()
        ->and(Route::has('verification.verify'))->toBeFalse()
        ->and(Route::has('verification.send'))->toBeFalse();
});

test('users with an unverified email can access the dashboard', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();
});

test('users with an unverified email can access the settings pages', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('appearance.edit'))
        ->assertOk();
});

test('new users can use the system right after registering without receiving a verification email', function () {
    Notification::fake();

    $this->post(route('register.store'), [
        'name' => 'Maria Souza',
        'email' => 'maria@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    $this->get(route('dashboard'))->assertOk();

    Notification::assertNothingSent();
});
