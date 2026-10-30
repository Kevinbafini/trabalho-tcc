<?php

/*
|--------------------------------------------------------------------------
| Dados de demonstração
|--------------------------------------------------------------------------
|
| Usuário administrador criado pelo "php artisan db:seed", já com os dados
| de demonstração. Cada usuário também pode ativar ou remover os dados de
| demonstração da própria conta em Configurações → Dados de demonstração.
|
*/

return [

    'administrador' => [
        'nome' => env('ADMIN_NOME', 'admin user'),
        'email' => env('ADMIN_EMAIL', 'admin@admin.com'),

        // Sem senha definida: "password" no ambiente local; em produção, uma
        // senha aleatória, exibida no terminal ao rodar o seeder.
        'senha' => env('ADMIN_SENHA'),
    ],

];
