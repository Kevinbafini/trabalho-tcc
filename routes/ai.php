<?php

use App\Mcp\Servers\SistemaAgricolaServer;
use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Facades\Mcp;

/*
|--------------------------------------------------------------------------
| Servidor MCP
|--------------------------------------------------------------------------
|
| As mesmas ferramentas do Assistente IA, para apps de IA externos. O app
| (Claude, ChatGPT...) descobre o OAuth pelas rotas .well-known, registra-se
| sozinho e abre o navegador: o usuário entra no sistema e autoriza a conexão
| (tela resources/views/mcp/authorize.blade.php). Depois disso, o app age
| como o usuário, só com os dados dele.
|
| O registro de clientes é público pela especificação (RFC 7591) e não dá
| acesso a nada sozinho; o limite por minuto evita que encham a tabela.
|
*/

Route::middleware('throttle:30,1')->group(function () {
    Mcp::oauthRoutes();
});

Mcp::web('/mcp', SistemaAgricolaServer::class)
    ->middleware(['auth:api', 'throttle:mcp'])
    ->name('mcp');

// Servidor local (php artisan mcp:start agricola), para testes com o MCP Inspector.
// Sem usuário autenticado, nenhuma ferramenta aparece.
Mcp::local('agricola', SistemaAgricolaServer::class);
