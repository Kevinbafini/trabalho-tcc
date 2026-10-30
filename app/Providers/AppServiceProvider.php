<?php

namespace App\Providers;

use App\Http\Controllers\DevLoginController;
use App\Mcp\Servers\SistemaAgricolaServer;
use App\Mcp\Tools\Ferramenta;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterval;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Number;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Passport\Passport;
use Symfony\Component\HttpFoundation\Response;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // O fluxo de dispositivo do OAuth (código digitado na TV) não é usado
        // pelos apps de IA; desligado, o Passport não registra as rotas dele.
        // Precisa ser aqui: o Passport registra as rotas antes do boot deste provider.
        Passport::$deviceCodeGrantEnabled = false;
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureDevLogin();
        $this->configureMcp();
    }

    /**
     * Servidor MCP (/mcp), por onde apps de IA externos (Claude, ChatGPT...)
     * se conectam ao sistema com OAuth (Passport).
     *
     * O token de acesso vale 1 hora e o de renovação 30 dias, trocado a cada
     * uso (o padrão do Passport é 1 ano, e um token vazado valeria esse tempo
     * todo). Uma conexão esquecida por um mês expira sozinha; a que está em
     * uso se renova sem o usuário perceber.
     */
    protected function configureMcp(): void
    {
        Passport::tokensExpireIn(CarbonInterval::hour());
        Passport::refreshTokensExpireIn(CarbonInterval::days(30));

        // Tela em que o usuário autoriza (ou nega) o app de IA a usar o sistema em seu nome.
        Passport::authorizationView(fn (array $parametros): Response => response()->view('mcp.authorize', [
            ...$parametros,
            'ferramentas' => collect(SistemaAgricolaServer::FERRAMENTAS)->map(fn (string $classe): Ferramenta => app($classe)),
        ]));

        // Uma resposta do app de IA encadeia várias ferramentas, então o limite é folgado,
        // e é por usuário: um token vazado não consegue repetir chamadas sem limite.
        RateLimiter::for('mcp', fn (Request $request): Limit => Limit::perMinute(120)
            ->by('mcp:'.($request->user()?->getAuthIdentifier() ?? $request->ip())));
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
