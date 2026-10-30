<x-layouts::auth :title="__('Log in')">
    <div class="flex flex-col gap-6">
        <x-auth-header title="Bem-vindo de volta" description="Entre com seu e-mail e sua senha para acessar o sistema." />

        <!-- Session Status -->
        <x-auth-session-status :status="session('status')" />

        <form
            method="POST"
            action="{{ route('login.store') }}"
            class="flex flex-col gap-5"
            x-data="{ enviando: false, capsLock: false }"
            x-on:submit="enviando = true"
        >
            @csrf

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="__('Email address')"
                :value="old('email')"
                type="email"
                icon="envelope"
                required
                autofocus
                autocomplete="username"
                placeholder="seuemail@exemplo.com"
            />

            <!-- Password -->
            <div x-on:keyup="capsLock = $event.getModifierState?.('CapsLock') ?? false" x-on:keydown="capsLock = $event.getModifierState?.('CapsLock') ?? false">
                <flux:input
                    name="password"
                    :label="__('Password')"
                    type="password"
                    icon="lock-closed"
                    required
                    autocomplete="current-password"
                    placeholder="Sua senha"
                    viewable
                />

                <p x-show="capsLock" x-cloak class="mt-2 flex items-center gap-1.5 text-xs text-amber-600 dark:text-amber-400" role="status">
                    <flux:icon.exclamation-triangle variant="micro" />
                    Caps Lock está ativado.
                </p>
            </div>

            <!-- Remember Me / Forgot Password -->
            <div class="flex items-center justify-between gap-4">
                <flux:checkbox name="remember" :label="__('Remember me')" :checked="old('remember')" />

                @if (Route::has('password.request'))
                    <flux:link class="text-sm" :href="route('password.request')" wire:navigate>
                        {{ __('Forgot your password?') }}
                    </flux:link>
                @endif
            </div>

            <flux:button variant="primary" type="submit" class="mt-1 w-full" x-bind:disabled="enviando" data-test="login-button">
                {{ __('Log in') }}
            </flux:button>
        </form>

        <x-passkey-verify separator="ou" separator-position="before" />

        <p class="text-center text-sm text-zinc-600 dark:text-zinc-400">
            {{ __('Don\'t have an account?') }}
            <flux:link :href="route('register')" wire:navigate>Crie sua conta</flux:link>
        </p>

        @include('livewire.auth.partials.login-rapido')
    </div>
</x-layouts::auth>
