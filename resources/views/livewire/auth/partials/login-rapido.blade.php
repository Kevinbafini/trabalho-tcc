{{-- Login rápido de desenvolvimento: só aparece com APP_ENV=local e DEV_LOGIN=true. --}}
@if (($usuariosDoLoginRapido ?? collect())->isNotEmpty())
    <div class="space-y-3 rounded-lg border border-dashed border-amber-400 p-4 dark:border-amber-500/60">
        <div>
            <flux:heading size="sm">Login rápido de desenvolvimento</flux:heading>
            <flux:text class="text-xs">Entra sem senha. Disponível apenas no ambiente local.</flux:text>
        </div>

        <form method="POST" action="{{ route('dev.login') }}" class="flex gap-2">
            @csrf

            <div class="min-w-0 flex-1">
                <flux:select name="user_id" aria-label="Usuário">
                    @foreach ($usuariosDoLoginRapido as $usuario)
                        <flux:select.option :value="$usuario->id">{{ $usuario->name }} ({{ $usuario->email }})</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <flux:button type="submit" icon="bolt" data-test="dev-login-button">Entrar</flux:button>
        </form>
    </div>
@endif
