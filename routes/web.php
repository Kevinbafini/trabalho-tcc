<?php

use App\Http\Controllers\AssistenteController;
use App\Http\Controllers\AtividadeAgricolaController;
use App\Http\Controllers\CustoTalhaoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DevLoginController;
use App\Http\Controllers\FuncionarioController;
use App\Http\Controllers\ImplementoController;
use App\Http\Controllers\InsumoController;
use App\Http\Controllers\MaquinaController;
use App\Http\Controllers\PropriedadeController;
use App\Http\Controllers\RelatorioController;
use App\Http\Controllers\RelatorioPdfController;
use App\Http\Controllers\TalhaoController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', 'dashboard')->name('home');

// Login rápido de desenvolvimento (responde 404 fora do ambiente local).
Route::post('dev/login', DevLoginController::class)->middleware('guest')->name('dev.login');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    // Gestão
    Route::resource('propriedades', PropriedadeController::class)
        ->parameters(['propriedades' => 'propriedade']);
    Route::resource('talhoes', TalhaoController::class)
        ->parameters(['talhoes' => 'talhao']);

    // Recursos
    Route::resource('funcionarios', FuncionarioController::class)
        ->except('show')
        ->parameters(['funcionarios' => 'funcionario']);
    Route::resource('maquinas', MaquinaController::class)
        ->except('show')
        ->parameters(['maquinas' => 'maquina']);
    Route::resource('implementos', ImplementoController::class)
        ->except('show')
        ->parameters(['implementos' => 'implemento']);
    Route::resource('insumos', InsumoController::class)
        ->except('show')
        ->parameters(['insumos' => 'insumo']);

    // Operações
    Route::resource('atividades', AtividadeAgricolaController::class)
        ->parameters(['atividades' => 'atividade']);

    // Análises
    Route::get('custos-por-talhao', CustoTalhaoController::class)->name('custos-talhoes.index');
    Route::get('relatorios', RelatorioController::class)->name('relatorios.index');
    Route::get('relatorios/pdf', RelatorioPdfController::class)->name('relatorios.pdf');

    // Inteligência artificial
    Route::controller(AssistenteController::class)->group(function () {
        Route::get('assistente', 'index')->name('assistente.index');
        Route::get('assistente/painel', 'painel')->name('assistente.painel');
        Route::post('assistente/mensagens', 'store')->name('assistente.mensagens.store');
        Route::delete('assistente/conversas/{conversa}', 'destroy')->name('assistente.conversas.destroy');
    });
});

require __DIR__.'/settings.php';
