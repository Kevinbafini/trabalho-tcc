<?php

use App\Models\User;
use Illuminate\Support\Facades\Validator;

test('validation messages are shown in brazilian portuguese', function () {
    $validator = Validator::make(['email' => ''], ['email' => 'required']);

    expect($validator->errors()->first('email'))->toBe('O campo e-mail é obrigatório.');
});

test('failed login shows a message in brazilian portuguese', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'senha-errada',
    ])->assertSessionHasErrors(['email' => 'E-mail ou senha incorretos.']);
});
