<x-layouts::app title="Assistente IA">
    <x-cabecalho-pagina
        titulo="Assistente IA"
        icone="sparkles"
        descricao="Pergunte sobre os custos ou peça para cadastrar dados. Nada é salvo sem a sua confirmação."
    >
        <x-slot:acoes>
            @if ($conversas->isNotEmpty())
                <flux:dropdown class="lg:hidden">
                    <flux:button icon="chat-bubble-left-right" icon:trailing="chevron-down">Conversas</flux:button>

                    <flux:menu>
                        @foreach ($conversas->take(10) as $item)
                            <flux:menu.item :href="route('assistente.index', ['conversa' => $item->id])" wire:navigate>
                                {{ Str::limit($item->titulo, 40) }}
                            </flux:menu.item>
                        @endforeach
                    </flux:menu>
                </flux:dropdown>
            @endif

            @if ($conversa)
                <x-confirmar-exclusao
                    :acao="route('assistente.conversas.destroy', $conversa)"
                    nome="conversa-{{ $conversa->id }}"
                    mensagem="A conversa “{{ $conversa->titulo }}” e todas as suas mensagens serão excluídas. Os cadastros feitos pelo assistente continuam no sistema."
                />
            @endif

            <flux:button :href="route('assistente.index')" variant="primary" icon="plus" wire:navigate>Nova conversa</flux:button>
        </x-slot:acoes>
    </x-cabecalho-pagina>

    <div class="grid gap-6 lg:grid-cols-[16rem_minmax(0,1fr)]">
        <aside
            class="hidden min-h-0 flex-col rounded-xl border border-zinc-200 bg-white lg:flex dark:border-white/10 dark:bg-zinc-900"
            x-data
            x-on:assistente-historico.window="$refs.lista.innerHTML = $event.detail.html"
        >
            <div class="border-b border-zinc-200 px-4 py-3 dark:border-white/10">
                <flux:heading>Conversas</flux:heading>
                <flux:text size="sm">As 50 mais recentes</flux:text>
            </div>

            <nav x-ref="lista" class="max-h-[calc(100dvh-16rem)] space-y-0.5 overflow-y-auto p-2" aria-label="Conversas">
                @include('assistente.partials.historico', ['conversaAtual' => $conversa])
            </nav>
        </aside>

        <section class="flex h-[calc(100dvh-13rem)] min-h-[30rem] min-w-0 flex-col overflow-hidden rounded-xl border border-zinc-200 bg-zinc-50/50 dark:border-white/10 dark:bg-zinc-900">
            @include('assistente.partials.chat', ['compacto' => false, 'comHistorico' => true])
        </section>
    </div>
</x-layouts::app>
