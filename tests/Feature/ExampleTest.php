<?php

use App\Models\User;

/*
 * Página pública de apresentação do sistema (rota "home", em /).
 */

test('a página inicial apresenta o sistema para os visitantes', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee(['Saiba quanto custa', 'Como funciona', 'Perguntas frequentes', 'Criar minha conta', 'Entrar'])
        ->assertSee(route('register'), false)
        ->assertDontSee('Ir para o Dashboard');
});

test('na página inicial, quem já entrou vê o atalho para o Dashboard', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('home'))
        ->assertOk()
        ->assertSee(['Ir para o Dashboard', route('dashboard')])
        ->assertDontSee('Criar minha conta');
});
