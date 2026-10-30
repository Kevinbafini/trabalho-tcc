{{--
    Uma mensagem do chat, no estilo dos chats de IA: a do usuário em um balão
    à direita; a da IA em texto corrido, com o botão de copiar e, discretos,
    o provedor, as ferramentas usadas e os tokens gastos.
--}}
@props([
    'mensagem',
])

@if ($mensagem->papel === \App\Enums\PapelMensagem::Assistente)
    <article class="group flex items-start gap-3 sm:gap-4" x-data="{ copiado: false }">
        <div class="flex size-8 shrink-0 items-center justify-center rounded-full border border-primary/15 bg-primary/8 text-primary dark:border-white/10 dark:bg-secondary/30 dark:text-accent-content" aria-hidden="true">
            <flux:icon.sparkles variant="micro" />
        </div>

        <div class="min-w-0 flex-1 pt-0.5">
            <div x-ref="texto" class="conteudo-ia">
                {!! $mensagem->conteudo_html !!}
            </div>

            <div class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-zinc-500 dark:text-zinc-400">
                <button
                    type="button"
                    class="-ms-1.5 inline-flex cursor-pointer items-center gap-1 rounded-md px-1.5 py-1 transition hover:bg-zinc-100 hover:text-zinc-800 dark:hover:bg-white/10 dark:hover:text-white"
                    x-on:click="navigator.clipboard.writeText($refs.texto.innerText).then(() => { copiado = true; setTimeout(() => copiado = false, 1500) })"
                    aria-label="Copiar resposta"
                >
                    <flux:icon.clipboard-document variant="micro" x-show="! copiado" />
                    <flux:icon.check variant="micro" x-show="copiado" x-cloak />
                    <span x-text="copiado ? 'Copiado' : 'Copiar'">Copiar</span>
                </button>

                @if ($mensagem->provedor)
                    <span title="{{ $mensagem->modelo }}">{{ $mensagem->provedor->rotulo() }}</span>
                @endif

                @if ($mensagem->ferramentas)
                    <span class="sr-only">Ferramentas usadas:</span>
                    @foreach (array_unique($mensagem->ferramentas) as $ferramenta)
                        <span class="inline-flex items-center gap-1 rounded-full bg-zinc-100 px-2 py-0.5 dark:bg-white/10">
                            <flux:icon.wrench-screwdriver variant="micro" class="size-3" />
                            {{ \App\Services\Ia\FerramentasDoAssistente::titulos()[$ferramenta] ?? $ferramenta }}
                        </span>
                    @endforeach
                @endif

                <span class="opacity-0 transition group-hover:opacity-100 focus-within:opacity-100" title="Tokens de entrada: {{ Number::format($mensagem->tokens_entrada) }} ({{ Number::format($mensagem->tokens_cache) }} do cache) · Tokens de saída: {{ Number::format($mensagem->tokens_saida) }}">
                    {{ Number::format($mensagem->tokens_entrada + $mensagem->tokens_saida) }} tokens · {{ $mensagem->created_at?->format('H:i') }}
                </span>
            </div>
        </div>
    </article>
@else
    <article class="flex justify-end">
        <div class="max-w-[85%] rounded-3xl bg-primary/8 px-5 py-2.5 text-[15px] leading-7 break-words text-zinc-900 sm:max-w-[75%] dark:bg-secondary/35 dark:text-white">
            {!! $mensagem->conteudo_html !!}
        </div>
    </article>
@endif
