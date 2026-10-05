<?php

test('home redirects to the dashboard', function () {
    $response = $this->get(route('home'));

    $response->assertRedirect(route('dashboard'));
});

test('guests opening the home page end up on the login page', function () {
    $this->followingRedirects()
        ->get(route('home'))
        ->assertOk()
        ->assertSee('Entre na sua conta');
});
