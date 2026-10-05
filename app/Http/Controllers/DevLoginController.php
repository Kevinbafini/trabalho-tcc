<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Login rápido para desenvolvimento: entra como o usuário escolhido, sem senha.
 *
 * Só funciona com as duas travas ligadas: ambiente local (APP_ENV=local)
 * e DEV_LOGIN=true. Em qualquer outro caso, a rota responde 404.
 */
class DevLoginController extends Controller
{
    /**
     * Indica se o login rápido está disponível neste ambiente.
     */
    public static function habilitado(): bool
    {
        return app()->isLocal() && config('app.dev_login') === true;
    }

    /**
     * Autentica o usuário escolhido na tela de login.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        abort_unless(self::habilitado(), 404);

        $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        Auth::login(User::query()->findOrFail($request->integer('user_id')));
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
