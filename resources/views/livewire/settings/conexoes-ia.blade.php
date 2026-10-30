<section class="w-full">
    @include('partials.settings-heading', ['ajuda' => 'conexoes-ia.edit'])

    <flux:heading level="2" class="sr-only">Conexões de IA</flux:heading>

    <x-settings.layout heading="Conexões de IA" subheading="Conecte o Claude, o ChatGPT e outros apps de IA ao sistema pelo protocolo MCP.">
        <div class="space-y-8">
            <div class="space-y-2">
                <flux:input
                    label="Endereço do servidor MCP"
                    :value="route('mcp')"
                    readonly
                    copyable
                />
                <flux:text size="sm">
                    Ao adicionar este endereço, o app abre o navegador para você entrar no sistema e autorizar a conexão. Ele passa a consultar e cadastrar só os seus dados, pedindo confirmação antes de gravar.
                </flux:text>
            </div>

            <div>
                <flux:heading size="sm">Como conectar</flux:heading>

                <ul class="mt-3 space-y-3 text-sm text-zinc-600 dark:text-zinc-300">
                    <li class="flex gap-2.5">
                        <flux:icon.sparkles variant="mini" class="mt-0.5 shrink-0 text-zinc-400" />
                        <span><strong class="font-medium text-zinc-900 dark:text-white">Claude</strong> (site ou aplicativo): adicione um conector personalizado e cole o endereço acima.</span>
                    </li>
                    <li class="flex gap-2.5">
                        <flux:icon.chat-bubble-left-right variant="mini" class="mt-0.5 shrink-0 text-zinc-400" />
                        <span><strong class="font-medium text-zinc-900 dark:text-white">ChatGPT</strong>: nas configurações de conectores (modo de desenvolvedor), crie um conector com o endereço acima.</span>
                    </li>
                    <li class="flex gap-2.5">
                        <flux:icon.command-line variant="mini" class="mt-0.5 shrink-0 text-zinc-400" />
                        <span class="min-w-0">
                            <strong class="font-medium text-zinc-900 dark:text-white">Claude Code</strong>: no terminal, rode
                            <code class="mt-1 block overflow-x-auto rounded-md bg-zinc-100 px-2 py-1.5 text-xs whitespace-nowrap dark:bg-white/10">claude mcp add --transport http agricola {{ route('mcp') }}</code>
                        </span>
                    </li>
                </ul>
            </div>

            <div>
                <flux:heading size="sm">Aplicativos conectados</flux:heading>

                @if ($this->conexoes->isEmpty())
                    <flux:text size="sm" class="mt-2">Nenhum aplicativo conectado.</flux:text>
                @else
                    <ul class="mt-3 divide-y divide-zinc-200 rounded-xl border border-zinc-200 dark:divide-white/10 dark:border-white/10">
                        @foreach ($this->conexoes as $conexao)
                            <li class="flex items-center justify-between gap-4 px-4 py-3" wire:key="conexao-{{ $conexao['cliente'] }}">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-zinc-900 dark:text-white">{{ $conexao['nome'] }}</p>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                        Conectado em {{ $conexao['conectado_em']?->format('d/m/Y') }} · último uso {{ $conexao['ultimo_uso']?->diffForHumans() }}
                                    </p>
                                </div>

                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    icon="x-mark"
                                    wire:click="desconectar(@js($conexao['cliente']))"
                                    wire:confirm="Desconectar {{ $conexao['nome'] }}? O aplicativo perde o acesso na hora."
                                >
                                    Desconectar
                                </flux:button>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </x-settings.layout>
</section>
