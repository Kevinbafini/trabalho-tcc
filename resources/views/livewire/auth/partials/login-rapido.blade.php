{{-- Login rápido de desenvolvimento: só aparece com APP_ENV=local e DEV_LOGIN=true. --}}
@if (($usuariosDoLoginRapido ?? collect())->isNotEmpty())
    <div class="rounded-xl border border-amber-200 bg-amber-50/70 p-3.5 dark:border-amber-400/20 dark:bg-amber-400/5">
        <div class="mb-2.5 flex items-center gap-2">
            <flux:icon.bolt variant="micro" class="text-amber-600 dark:text-amber-400" />
            <p class="text-xs font-semibold text-amber-800 dark:text-amber-300">Login rápido de desenvolvimento</p>
            <span class="ms-auto hidden text-[11px] text-amber-700/80 sm:inline dark:text-amber-300/70">só no ambiente local</span>
        </div>

        <form method="POST" action="{{ route('dev.login') }}" class="flex gap-2">
            @csrf

            <div class="min-w-0 flex-1">
                <flux:select name="user_id" size="sm" aria-label="Usuário">
                    @foreach ($usuariosDoLoginRapido as $usuario)
                        <flux:select.option :value="$usuario->id">{{ $usuario->name }} ({{ $usuario->email }})</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <flux:button type="submit" size="sm" data-test="dev-login-button">Entrar</flux:button>
        </form>
    </div>
@endif
