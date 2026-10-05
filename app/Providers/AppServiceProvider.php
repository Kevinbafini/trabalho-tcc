<?php

namespace App\Providers;

use App\Http\Controllers\DevLoginController;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Number;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureDevLogin();
    }

    /**
     * Entrega à tela de login a lista de usuários do login rápido,
     * somente quando ele está habilitado (ambiente local + DEV_LOGIN=true).
     */
    protected function configureDevLogin(): void
    {
        View::composer('livewire.auth.login', function (\Illuminate\View\View $view): void {
            $view->with('usuariosDoLoginRapido', DevLoginController::habilitado()
                ? User::query()->orderBy('id')->limit(100)->get(['id', 'name', 'email'])
                : collect());
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        // Números e valores monetários no padrão brasileiro (ex.: R$ 1.234,56).
        Number::useLocale('pt_BR');
        Number::useCurrency('BRL');

        // URLs dos formulários em português (ex.: /talhoes/cadastrar, /talhoes/1/editar).
        Route::resourceVerbs([
            'create' => 'cadastrar',
            'edit' => 'editar',
        ]);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
