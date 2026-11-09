<?php

use App\Enums\EscopoDaApi;
use App\Http\Controllers\Api\V1\AtividadeAgricolaController;
use App\Http\Controllers\Api\V1\AutenticacaoController;
use App\Http\Controllers\Api\V1\FuncionarioController;
use App\Http\Controllers\Api\V1\ImplementoController;
use App\Http\Controllers\Api\V1\InsumoController;
use App\Http\Controllers\Api\V1\MaquinaController;
use App\Http\Controllers\Api\V1\PropriedadeController;
use App\Http\Controllers\Api\V1\RelatorioDeCustosController;
use App\Http\Controllers\Api\V1\TalhaoController;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Http\Middleware\CheckToken;

/*
|--------------------------------------------------------------------------
| API REST — versão 1 (/api/v1)
|--------------------------------------------------------------------------
|
| Rotas para aplicações externas: recebem e devolvem JSON, sem sessão.
| O cliente faz login em POST /api/v1/login, recebe um token e o envia nas
| demais requisições, no cabeçalho "Authorization: Bearer {token}".
|
| Proteções, na ordem em que são aplicadas:
|   auth:api      token válido (sem token ou com token inválido → 401)
|   throttle:api  até 60 requisições por minuto por usuário (excesso → 429)
|   CheckToken    escopo do token: api:leitura para consultas (GET) e
|                 api:escrita para cadastrar, alterar e excluir (sem o escopo → 403)
|
| Cada usuário só acessa os dados das próprias propriedades, como no sistema
| web: o registro de outro usuário responde 404, sem revelar que existe.
|
*/

Route::prefix('v1')->name('api.v1.')->group(function () {
    // Mesmo limite do login web: 5 tentativas por minuto por e-mail e IP.
    Route::post('login', [AutenticacaoController::class, 'login'])
        ->middleware('throttle:login')
        ->name('login');

    Route::middleware(['auth:api', 'throttle:api'])->group(function () {
        Route::post('logout', [AutenticacaoController::class, 'logout'])->name('logout');

        Route::middleware(CheckToken::using(EscopoDaApi::Leitura->value))->group(function () {
            Route::get('usuario', [AutenticacaoController::class, 'usuario'])->name('usuario');

            // Gestão
            Route::apiResource('propriedades', PropriedadeController::class)
                ->only(['index', 'show'])
                ->parameters(['propriedades' => 'propriedade']);
            Route::apiResource('talhoes', TalhaoController::class)
                ->only(['index', 'show'])
                ->parameters(['talhoes' => 'talhao']);

            // Recursos (os IDs usados no cadastro de atividades)
            Route::apiResource('funcionarios', FuncionarioController::class)->only('index');
            Route::apiResource('maquinas', MaquinaController::class)->only('index');
            Route::apiResource('implementos', ImplementoController::class)->only('index');
            Route::apiResource('insumos', InsumoController::class)->only('index');

            // Operações
            Route::apiResource('atividades', AtividadeAgricolaController::class)
                ->only(['index', 'show'])
                ->parameters(['atividades' => 'atividade']);

            // Análises
            Route::get('relatorios/custos', RelatorioDeCustosController::class)->name('relatorios.custos');
        });

        Route::middleware(CheckToken::using(EscopoDaApi::Escrita->value))->group(function () {
            Route::apiResource('atividades', AtividadeAgricolaController::class)
                ->only(['store', 'update', 'destroy'])
                ->parameters(['atividades' => 'atividade']);
        });
    });
});
