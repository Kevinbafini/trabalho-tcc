{{--
    Tela de autorização do OAuth: abre quando um app de IA (Claude, ChatGPT...)
    pede para usar o sistema em nome do usuário (Passport::authorizationView,
    no AppServiceProvider).

    O nome do app é informado pelo próprio app (registro dinâmico), por isso a
    tela avisa isso e mostra para onde o código de acesso volta: é o que
    denuncia um "Claude" falso.
--}}
@php
    $enderecoDeRetorno = (string) request()->query('redirect_uri', $client->redirect_uris[0] ?? '');
    $esquema = strtolower((string) parse_url($enderecoDeRetorno, PHP_URL_SCHEME));
    $servidor = strtolower((string) parse_url($enderecoDeRetorno, PHP_URL_HOST));

    $destino = match (true) {
        in_array($servidor, ['localhost', '127.0.0.1', '[::1]'], true) => 'um aplicativo neste computador',
        in_array($esquema, ['http', 'https'], true) && $servidor !== '' => $servidor,
        $esquema !== '' => "o aplicativo {$esquema} instalado neste computador",
        default => 'o aplicativo que pediu a conexão',
    };

    $nomeDoApp = filled($client->name) ? $client->name : 'Um aplicativo';
@endphp

<x-layouts::auth title="Autorizar conexão de IA">
    <div class="flex flex-col gap-6">
        <div class="text-center">
            <flux:heading size="xl">{{ $nomeDoApp }} quer acessar o sistema como você</flux:heading>
            <flux:text class="mt-2">
                O nome acima é informado pelo próprio aplicativo. Só autorize se foi você quem acabou de pedir esta conexão no Claude, no ChatGPT ou em outro app de IA.
            </flux:text>
        </div>

        <dl class="grid gap-3 rounded-xl border border-zinc-200 p-4 text-sm dark:border-white/10">
            <div class="flex flex-wrap items-start justify-between gap-x-4 gap-y-1">
                <dt class="text-zinc-500 dark:text-zinc-400">Conta</dt>
                <dd class="min-w-0 text-end text-zinc-900 dark:text-white">
                    {{ $user->name }}
                    <span class="block text-xs break-all text-zinc-500 dark:text-zinc-400">{{ $user->email }}</span>
                </dd>
            </div>

            <div class="flex flex-wrap items-start justify-between gap-x-4 gap-y-1">
                <dt class="text-zinc-500 dark:text-zinc-400">Depois de autorizar, volta para</dt>
                <dd class="min-w-0 text-end font-medium break-all text-zinc-900 dark:text-white">{{ $destino }}</dd>
            </div>
        </dl>

        <section aria-labelledby="titulo-ferramentas">
            <flux:heading id="titulo-ferramentas" size="sm" class="mb-2">O que o aplicativo poderá fazer</flux:heading>

            <ul class="grid gap-1.5 text-sm text-zinc-700 dark:text-zinc-300">
                @foreach ($ferramentas as $ferramenta)
                    @php($somenteLeitura = ($ferramenta->annotations()['readOnlyHint'] ?? false) === true)
                    <li class="flex items-start gap-2">
                        <flux:icon :name="$somenteLeitura ? 'eye' : 'plus-circle'" variant="micro" class="mt-0.5 shrink-0 {{ $somenteLeitura ? 'text-zinc-400' : 'text-amber-500' }}" />
                        <span>{{ $ferramenta->title() }}</span>
                    </li>
                @endforeach
            </ul>

            <flux:text size="sm" class="mt-3">
                Somente com os seus dados. O app consulta e cadastra, mas não edita nem exclui, e pede a sua confirmação antes de gravar. Para desconectar: Configurações → Conexões de IA.
            </flux:text>
        </section>

        <div class="flex flex-col-reverse gap-3 sm:flex-row">
            <form method="POST" action="{{ route('passport.authorizations.deny') }}" class="flex-1">
                @csrf
                @method('DELETE')
                <input type="hidden" name="auth_token" value="{{ $authToken }}">

                <flux:button type="submit" class="w-full">Cancelar</flux:button>
            </form>

            <form method="POST" action="{{ route('passport.authorizations.approve') }}" class="flex-1" x-data="{ enviando: false }" x-on:submit="enviando = true">
                @csrf
                <input type="hidden" name="auth_token" value="{{ $authToken }}">

                <flux:button type="submit" variant="primary" class="w-full" x-bind:disabled="enviando">
                    <span x-show="! enviando">Autorizar</span>
                    <span x-show="enviando" x-cloak>Autorizando…</span>
                </flux:button>
            </form>
        </div>
    </div>
</x-layouts::auth>
