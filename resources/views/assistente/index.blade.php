{{--
    Assistente IA em tela cheia, no estilo dos chats de IA: conversas à
    esquerda (recolhível; no celular abre como gaveta) e o chat ocupando o resto.
--}}
<x-layouts::app title="Assistente IA" tela-cheia>
    <div
        class="flex h-[calc(100dvh-3.5rem)] lg:h-dvh"
        x-data="{
            conversasAbertas: (() => { try { return localStorage.getItem('assistente-conversas') !== 'fechadas' } catch { return true } })(),
            alternarConversas() {
                this.conversasAbertas = ! this.conversasAbertas;
                try { localStorage.setItem('assistente-conversas', this.conversasAbertas ? 'abertas' : 'fechadas') } catch {}
            },
        }"
        x-on:alternar-conversas.window="alternarConversas()"
    >
        <aside x-show="conversasAbertas" class="hidden w-64 shrink-0 flex-col border-e border-zinc-200 bg-zinc-50 md:flex dark:border-white/10 dark:bg-zinc-900">
            <div class="p-2">
                <a
                    href="{{ route('assistente.index') }}"
                    wire:navigate
                    class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium text-zinc-800 transition hover:bg-zinc-200/50 dark:text-zinc-100 dark:hover:bg-white/5"
                >
                    <flux:icon.pencil-square variant="mini" class="text-zinc-500" />
                    Nova conversa
                </a>
            </div>

            <nav x-data x-on:assistente-historico.window="$el.innerHTML = $event.detail.html" class="min-h-0 flex-1 overflow-y-auto px-2 pb-4" aria-label="Conversas">
                @include('assistente.partials.historico', ['conversaAtual' => $conversa])
            </nav>

            <div class="border-t border-zinc-200 px-4 py-3 text-xs text-zinc-500 dark:border-white/10 dark:text-zinc-400">
                Respostas do Claude ou da OpenAI, com os seus dados. As conversas ficam só na sua conta.
            </div>
        </aside>

        <section class="flex min-w-0 flex-1 flex-col bg-white dark:bg-zinc-800">
            @include('assistente.partials.chat', ['variante' => 'tela'])
        </section>

        {{-- Conversas no celular --}}
        <flux:modal name="conversas-do-assistente" variant="flyout" position="left" class="w-72 p-0!">
            <div class="flex h-dvh flex-col bg-zinc-50 dark:bg-zinc-900">
                <div class="flex items-center gap-2 border-b border-zinc-200 py-3 ps-4 pe-12 dark:border-white/10">
                    <flux:heading>Conversas</flux:heading>
                </div>

                <div class="p-2">
                    <a
                        href="{{ route('assistente.index') }}"
                        wire:navigate
                        class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium text-zinc-800 transition hover:bg-zinc-200/50 dark:text-zinc-100 dark:hover:bg-white/5"
                    >
                        <flux:icon.pencil-square variant="mini" class="text-zinc-500" />
                        Nova conversa
                    </a>
                </div>

                <nav x-data x-on:assistente-historico.window="$el.innerHTML = $event.detail.html" class="min-h-0 flex-1 overflow-y-auto px-2 pb-4" aria-label="Conversas">
                    @include('assistente.partials.historico', ['conversaAtual' => $conversa])
                </nav>
            </div>
        </flux:modal>
    </div>
</x-layouts::app>
