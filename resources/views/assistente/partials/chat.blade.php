{{--
    Chat do Assistente IA, no estilo dos chats de IA (componente Alpine
    chatDoAssistente, em resources/js/assistente.js).

    $variante: "tela" (tela cheia, com barra superior completa e o início de
    conversa centralizado) ou "painel" (painel lateral, mais compacto).
    Variáveis: $conversa, $provedores, $provedorSelecionado, $disponivel, $restantes.
--}}
@php
    $telaCheia = ($variante ?? 'tela') === 'tela';
    $vazia = $conversa === null || $conversa->mensagens->isEmpty();
    $nomes = collect($provedores)->mapWithKeys(fn (array $item): array => [$item['provedor']->value => $item['provedor']->rotulo()])->all();
    $modelos = collect($provedores)->mapWithKeys(fn (array $item): array => [$item['provedor']->value => $item['modelo']])->all();
@endphp

<div
    x-data="chatDoAssistente({
        conversaId: @js($conversa?->id),
        provedor: @js($provedorSelecionado->value),
        restantes: @js($restantes),
        vazia: @js($telaCheia && $vazia),
        telaCheia: @js($telaCheia),
    })"
    class="flex min-h-0 flex-1 flex-col"
>
    {{-- Barra superior: provedor de IA (como o seletor de modelo) e ações --}}
    <div class="flex h-14 shrink-0 items-center gap-1 px-2 sm:px-3">
        @if ($telaCheia)
            <div class="md:hidden">
                <flux:modal.trigger name="conversas-do-assistente">
                    <flux:button variant="ghost" icon="chat-bubble-left-right" aria-label="Abrir as conversas" />
                </flux:modal.trigger>
            </div>
            <div class="hidden md:block">
                <flux:button
                    variant="ghost"
                    icon="view-columns"
                    x-on:click="$dispatch('alternar-conversas')"
                    tooltip="Mostrar ou ocultar as conversas"
                    aria-label="Mostrar ou ocultar as conversas"
                />
            </div>
        @endif

        <flux:dropdown position="bottom" align="start">
            <button type="button" class="inline-flex max-w-full cursor-pointer items-center gap-2 rounded-lg px-2.5 py-1.5 transition hover:bg-zinc-100 dark:hover:bg-white/10" aria-label="Escolher o provedor de IA">
                <span class="text-base font-semibold text-zinc-900 sm:text-lg dark:text-white" x-text="@js($nomes)[provedor]">{{ $provedorSelecionado->rotulo() }}</span>
                <span class="hidden truncate text-sm text-zinc-400 sm:inline" x-text="@js($modelos)[provedor]">{{ $modelos[$provedorSelecionado->value] ?? '' }}</span>
                <flux:icon.chevron-down variant="micro" class="shrink-0 text-zinc-400" />
            </button>

            <flux:menu class="min-w-72">
                <flux:menu.heading>Provedor de IA</flux:menu.heading>

                @foreach ($provedores as $item)
                    @php($valor = $item['provedor']->value)
                    <flux:menu.item x-on:click="provedor = @js($valor)" :disabled="! $item['configurado']">
                        <div class="flex w-full items-center gap-3 py-0.5">
                            <div class="min-w-0 flex-1">
                                <div class="font-medium">{{ $item['provedor']->rotulo() }}</div>
                                <div class="truncate text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ $item['configurado'] ? $item['provedor']->empresa().' · '.$item['modelo'] : 'Chave da API não configurada' }}
                                </div>
                            </div>
                            <flux:icon.check variant="mini" class="shrink-0" x-show="provedor === @js($valor)" />
                        </div>
                    </flux:menu.item>
                @endforeach
            </flux:menu>
        </flux:dropdown>

        <flux:spacer />

        @if ($telaCheia)
            <div class="px-1">
                <x-ajuda-da-tela tela="assistente.index" icone="sparkles" />
            </div>

            <flux:button variant="ghost" icon="pencil-square" :href="route('assistente.index')" wire:navigate tooltip="Nova conversa" aria-label="Nova conversa" />

            <div x-show="conversaId" @if ($conversa === null) style="display: none" @endif>
                <flux:dropdown align="end">
                    <flux:button variant="ghost" icon="ellipsis-horizontal" aria-label="Mais opções" />

                    <flux:menu>
                        <flux:modal.trigger name="excluir-conversa">
                            <flux:menu.item icon="trash" variant="danger">Excluir conversa</flux:menu.item>
                        </flux:modal.trigger>
                    </flux:menu>
                </flux:dropdown>
            </div>
        @endif
    </div>

    {{-- Conversa. Vazia na tela cheia: saudação, campo de mensagem e sugestões ficam no centro. --}}
    <div class="flex min-h-0 flex-1 flex-col" x-bind:class="{ 'justify-center pb-[12vh]': vazia }" @class(['justify-center pb-[12vh]' => $telaCheia && $vazia])>
        <div x-ref="rolagem" x-show="! vazia" class="min-h-0 flex-1 overflow-y-auto" @if ($telaCheia && $vazia) style="display: none" @endif>
            <div @class(['mx-auto w-full max-w-3xl px-4 pt-2 pb-10 sm:px-6' => $telaCheia, 'px-4 py-5' => ! $telaCheia])>
                @if ($telaCheia)
                    <x-mensagens />
                @endif

                @unless ($disponivel)
                    @include('assistente.partials.nao-configurado')
                @endunless

                <div x-ref="mensagens" class="flex flex-col gap-8" aria-live="polite">
                    @include('assistente.partials.mensagens', ['conversa' => $conversa, 'comVazio' => ! $telaCheia])
                </div>

                {{-- Enquanto a IA responde: a mensagem enviada e o indicador de "pensando" --}}
                <template x-if="enviando">
                    <div class="mt-8 flex flex-col gap-8">
                        <div class="flex justify-end">
                            <div class="max-w-[85%] rounded-3xl bg-zinc-100 px-5 py-2.5 text-[15px] leading-7 break-words whitespace-pre-wrap text-zinc-900 sm:max-w-[75%] dark:bg-white/10 dark:text-white" x-text="pendente"></div>
                        </div>

                        <div class="flex items-center gap-3 sm:gap-4" role="status">
                            <div class="flex size-8 shrink-0 items-center justify-center rounded-full border border-zinc-200 bg-white text-zinc-700 dark:border-white/15 dark:bg-white/5 dark:text-zinc-200" aria-hidden="true">
                                <flux:icon.sparkles variant="micro" class="animate-pulse" />
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="size-2 animate-bounce rounded-full bg-zinc-400 [animation-delay:-0.3s]"></span>
                                <span class="size-2 animate-bounce rounded-full bg-zinc-400 [animation-delay:-0.15s]"></span>
                                <span class="size-2 animate-bounce rounded-full bg-zinc-400"></span>
                            </div>
                            <span class="text-sm text-zinc-500 dark:text-zinc-400">Consultando os dados…</span>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        @if ($telaCheia)
            <div x-show="vazia" class="mx-auto w-full max-w-3xl px-4 pb-6 text-center sm:px-6" @unless ($vazia) style="display: none" @endunless>
                @unless ($disponivel)
                    <div class="mb-6 text-start">
                        @include('assistente.partials.nao-configurado')
                    </div>
                @endunless

                <h1 class="text-2xl font-semibold tracking-tight text-zinc-900 sm:text-3xl dark:text-white">
                    Como posso ajudar, {{ Str::before(auth()->user()->name, ' ') }}?
                </h1>
                <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                    Pergunte sobre os custos ou peça para cadastrar algo. Nada é salvo sem a sua confirmação.
                </p>
            </div>
        @endif

        {{-- Campo de mensagem --}}
        <div @class(['mx-auto w-full max-w-3xl px-3 pb-3 sm:px-6 sm:pb-4' => $telaCheia, 'px-3 pb-3' => ! $telaCheia])>
            <div x-show="erro" x-cloak class="mb-2" role="alert">
                <div class="flex items-start gap-2 rounded-2xl border border-red-200 bg-red-50 px-4 py-2.5 text-sm text-red-700 dark:border-red-400/30 dark:bg-red-400/10 dark:text-red-200">
                    <flux:icon.exclamation-triangle variant="mini" class="mt-0.5 shrink-0" />
                    <span x-text="erro"></span>
                </div>
            </div>

            <form
                x-ref="formulario"
                method="POST"
                action="{{ route('assistente.mensagens.store') }}"
                x-on:submit.prevent="enviar()"
            >
                @csrf
                <input type="hidden" name="provedor" value="{{ $provedorSelecionado->value }}" x-bind:value="provedor">
                <input type="hidden" name="conversa_id" value="{{ $conversa?->id }}" x-bind:value="conversaId ?? ''">
                @if ($telaCheia)
                    <input type="hidden" name="tela_cheia" value="1">
                @endif

                <div class="rounded-[26px] border border-zinc-200 bg-white px-3 py-2 shadow-[0_4px_20px_rgba(0,0,0,0.05)] transition focus-within:border-zinc-300 dark:border-white/10 dark:bg-zinc-900 dark:shadow-none dark:focus-within:border-white/20">
                    <textarea
                        x-ref="campo"
                        name="mensagem"
                        x-model="mensagem"
                        x-on:keydown.enter="teclaEnter($event)"
                        x-on:input="ajustarAltura()"
                        rows="1"
                        maxlength="{{ config('assistente.tamanho_da_mensagem') }}"
                        placeholder="Pergunte alguma coisa ou peça um cadastro…"
                        aria-label="Mensagem para o assistente"
                        class="block max-h-52 min-h-10 w-full resize-none bg-transparent px-2 py-2 text-[15px] text-zinc-900 outline-none placeholder:text-zinc-400 dark:text-white"
                        @disabled(! $disponivel)
                    ></textarea>

                    <div class="flex items-center gap-2 ps-2">
                        <span class="hidden text-xs text-zinc-400 sm:inline">Enter envia · Shift + Enter quebra a linha</span>

                        <button
                            type="submit"
                            class="ms-auto flex size-9 cursor-pointer items-center justify-center rounded-full bg-zinc-900 text-white transition hover:bg-zinc-700 disabled:cursor-not-allowed disabled:bg-zinc-200 disabled:text-zinc-400 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200 dark:disabled:bg-white/10 dark:disabled:text-zinc-500"
                            x-bind:disabled="enviando || mensagem.trim() === ''"
                            aria-label="Enviar mensagem"
                            disabled
                        >
                            <flux:icon.arrow-up variant="mini" />
                        </button>
                    </div>
                </div>
            </form>

            <p class="mt-2 text-center text-xs text-zinc-400">
                O assistente pode errar: confira a prévia antes de confirmar um cadastro.
                <span class="whitespace-nowrap">·
                    <span x-text="restantes === 1 ? '1 mensagem restante hoje' : restantes + ' mensagens restantes hoje'">{{ trans_choice('{1} :count mensagem restante hoje|[0,*] :count mensagens restantes hoje', $restantes, ['count' => $restantes]) }}</span>
                </span>
            </p>
        </div>

        @if ($telaCheia)
            <div x-show="vazia" class="mx-auto flex w-full max-w-3xl flex-wrap justify-center gap-2 px-4 sm:px-6" @unless ($vazia) style="display: none" @endunless>
                @foreach (config('assistente.sugestoes') as $sugestao)
                    <button
                        type="button"
                        x-on:click="usarSugestao(@js($sugestao['texto']))"
                        class="inline-flex cursor-pointer items-center gap-2 rounded-full border border-zinc-200 bg-white px-3.5 py-2 text-sm text-zinc-700 transition hover:bg-zinc-50 dark:border-white/10 dark:bg-white/5 dark:text-zinc-200 dark:hover:bg-white/10"
                    >
                        <flux:icon :name="$sugestao['icone']" variant="mini" class="shrink-0 text-zinc-400" />
                        {{ $sugestao['texto'] }}
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    @if ($telaCheia)
        {{-- Exclusão da conversa atual (o endereço usa o ID da conversa em andamento) --}}
        <flux:modal name="excluir-conversa" class="max-w-md">
            <form method="POST" x-bind:action="@js(route('assistente.conversas.destroy', '__ID__')).replace('__ID__', conversaId)" class="space-y-6">
                @csrf
                @method('DELETE')

                <div>
                    <flux:heading size="lg">Excluir conversa?</flux:heading>
                    <flux:text class="mt-2">A conversa e todas as suas mensagens serão excluídas. Os cadastros feitos pelo assistente continuam no sistema.</flux:text>
                </div>

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancelar</flux:button>
                    </flux:modal.close>

                    <flux:button type="submit" variant="danger">Excluir</flux:button>
                </div>
            </form>
        </flux:modal>
    @endif
</div>
