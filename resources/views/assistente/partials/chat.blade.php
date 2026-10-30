{{--
    Chat do Assistente IA (componente Alpine chatDoAssistente, em resources/js/assistente.js).
    Usado na tela cheia e no painel lateral ($compacto).

    Variáveis: $conversa, $provedores, $provedorSelecionado, $disponivel, $restantes,
    $compacto (painel lateral) e $comHistorico (a tela cheia também atualiza o histórico).
--}}
@php
    $compacto ??= false;
    $comHistorico ??= false;
    $modelos = collect($provedores)->mapWithKeys(fn (array $item): array => [$item['provedor']->value => $item['modelo']])->all();
@endphp

<div
    x-data="chatDoAssistente({
        conversaId: @js($conversa?->id),
        provedor: @js($provedorSelecionado->value),
        restantes: @js($restantes),
        atualizarEndereco: @js(! $compacto),
    })"
    class="flex min-h-0 flex-1 flex-col"
>
    {{-- Provedor de IA da próxima resposta --}}
    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-zinc-200 px-4 py-2.5 dark:border-white/10">
        <div class="inline-flex rounded-lg border border-zinc-200 bg-zinc-50 p-0.5 dark:border-white/10 dark:bg-white/5" role="radiogroup" aria-label="Provedor de IA">
            @foreach ($provedores as $item)
                @php($valor = $item['provedor']->value)
                <button
                    type="button"
                    role="radio"
                    x-bind:aria-checked="provedor === @js($valor)"
                    x-on:click="provedor = @js($valor)"
                    x-bind:class="provedor === @js($valor) ? 'bg-white text-zinc-900 shadow-xs dark:bg-white/15 dark:text-white' : 'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-white'"
                    class="inline-flex cursor-pointer items-center gap-1.5 rounded-md px-2.5 py-1 text-xs font-medium transition disabled:cursor-not-allowed disabled:opacity-40"
                    title="{{ $item['configurado'] ? $item['provedor']->empresa().' · '.$item['modelo'] : 'Chave da API não configurada' }}"
                    @disabled(! $item['configurado'])
                >
                    <span class="size-1.5 rounded-full {{ $item['configurado'] ? 'bg-emerald-500' : 'bg-zinc-400' }}" aria-hidden="true"></span>
                    {{ $item['provedor']->rotulo() }}
                </button>
            @endforeach
        </div>

        <span class="truncate text-[11px] text-zinc-400" x-text="'Modelo: ' + (@js($modelos)[provedor] ?? '')">Modelo: {{ $modelos[$provedorSelecionado->value] ?? '' }}</span>
    </div>

    {{-- Mensagens --}}
    <div x-ref="rolagem" class="min-h-0 flex-1 overflow-y-auto px-4 py-5 {{ $compacto ? '' : 'sm:px-6' }}">
        @unless ($disponivel)
            <flux:callout variant="warning" icon="key" class="mb-5">
                <flux:callout.heading>Assistente ainda não configurado</flux:callout.heading>
                <flux:callout.text>
                    Para ativá-lo, cadastre ANTHROPIC_API_KEY (Claude) ou OPENAI_API_KEY (OpenAI) no arquivo .env do servidor.
                </flux:callout.text>
            </flux:callout>
        @endunless

        <div x-ref="mensagens" class="flex flex-col gap-5" aria-live="polite">
            @include('assistente.partials.mensagens', ['conversa' => $conversa])
        </div>

        {{-- Enquanto a IA responde: a mensagem enviada e o indicador de "pensando" --}}
        <template x-if="enviando">
            <div class="mt-5 flex flex-col gap-5">
                <div class="flex justify-end">
                    <div class="max-w-[85%] rounded-2xl rounded-tr-md bg-zinc-900 px-4 py-2.5 text-sm leading-6 break-words whitespace-pre-wrap text-white dark:bg-zinc-100 dark:text-zinc-900" x-text="pendente"></div>
                </div>

                <div class="flex items-center gap-3" role="status">
                    <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-zinc-900 text-white dark:bg-white dark:text-zinc-900" aria-hidden="true">
                        <flux:icon.sparkles variant="micro" />
                    </div>
                    <div class="flex items-center gap-1.5 rounded-2xl rounded-tl-md border border-zinc-200 bg-white px-4 py-3 dark:border-white/10 dark:bg-white/5">
                        <span class="size-1.5 animate-bounce rounded-full bg-zinc-400 [animation-delay:-0.3s]"></span>
                        <span class="size-1.5 animate-bounce rounded-full bg-zinc-400 [animation-delay:-0.15s]"></span>
                        <span class="size-1.5 animate-bounce rounded-full bg-zinc-400"></span>
                    </div>
                    <span class="text-xs text-zinc-500 dark:text-zinc-400">Consultando os dados…</span>
                </div>
            </div>
        </template>
    </div>

    {{-- Erro da última mensagem (provedor fora do ar, limite de uso...) --}}
    <div x-show="erro" x-cloak class="px-4 pb-2" role="alert">
        <div class="flex items-start gap-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2.5 text-sm text-red-700 dark:border-red-400/30 dark:bg-red-400/10 dark:text-red-200">
            <flux:icon.exclamation-triangle variant="mini" class="mt-0.5 shrink-0" />
            <span x-text="erro"></span>
        </div>
    </div>

    {{-- Envio --}}
    <form
        x-ref="formulario"
        method="POST"
        action="{{ route('assistente.mensagens.store') }}"
        x-on:submit.prevent="enviar()"
        class="border-t border-zinc-200 p-3 dark:border-white/10"
    >
        @csrf
        <input type="hidden" name="provedor" value="{{ $provedorSelecionado->value }}" x-bind:value="provedor">
        <input type="hidden" name="conversa_id" value="{{ $conversa?->id }}" x-bind:value="conversaId ?? ''">
        @if ($comHistorico)
            <input type="hidden" name="com_historico" value="1">
        @endif

        <div class="flex items-end gap-2 rounded-xl border border-zinc-300 bg-white py-2 ps-3 pe-2 transition focus-within:border-zinc-400 focus-within:ring-2 focus-within:ring-zinc-900/5 dark:border-white/15 dark:bg-white/5 dark:focus-within:border-white/30">
            <textarea
                x-ref="campo"
                name="mensagem"
                x-model="mensagem"
                x-on:keydown.enter="teclaEnter($event)"
                x-on:input="ajustarAltura()"
                rows="1"
                maxlength="{{ config('assistente.tamanho_da_mensagem') }}"
                placeholder="Pergunte sobre os custos ou peça para cadastrar algo…"
                aria-label="Mensagem para o assistente"
                class="max-h-40 min-h-7 flex-1 resize-none bg-transparent py-1 text-sm text-zinc-900 outline-none placeholder:text-zinc-400 dark:text-white"
                @disabled(! $disponivel)
            ></textarea>

            <flux:button
                type="submit"
                variant="primary"
                size="sm"
                icon="paper-airplane"
                square
                :loading="false"
                x-bind:disabled="enviando || mensagem.trim() === ''"
                aria-label="Enviar mensagem"
            />
        </div>

        <div class="mt-1.5 flex flex-wrap justify-between gap-x-3 gap-y-1 px-1 text-[11px] text-zinc-400">
            <span>Enter envia · Shift + Enter quebra a linha · A IA pode errar: confira as prévias.</span>
            <span x-text="restantes === 1 ? '1 mensagem restante hoje' : restantes + ' mensagens restantes hoje'">{{ trans_choice('{1} :count mensagem restante hoje|[0,*] :count mensagens restantes hoje', $restantes, ['count' => $restantes]) }}</span>
        </div>
    </form>
</div>
