{{--
    Uma mensagem do chat. A do usuário fica à direita; a da IA à esquerda, com
    o provedor, as ferramentas que ela usou e os tokens gastos na resposta.
--}}
@props([
    'mensagem',
])

@if ($mensagem->papel === \App\Enums\PapelMensagem::Assistente)
    <article class="flex items-start gap-3">
        <div class="mt-5 flex size-8 shrink-0 items-center justify-center rounded-lg bg-zinc-900 text-white dark:bg-white dark:text-zinc-900" aria-hidden="true">
            <flux:icon.sparkles variant="micro" />
        </div>

        <div class="min-w-0 flex-1">
            <div class="mb-1 flex items-center gap-1.5 px-1 text-xs text-zinc-500 dark:text-zinc-400">
                <span class="font-medium text-zinc-700 dark:text-zinc-200">Assistente</span>
                @if ($mensagem->provedor)
                    <span aria-hidden="true">·</span>
                    <span title="{{ $mensagem->modelo }}">{{ $mensagem->provedor->rotulo() }}</span>
                @endif
                <span aria-hidden="true">·</span>
                <time datetime="{{ $mensagem->created_at?->toIso8601String() }}">{{ $mensagem->created_at?->format('H:i') }}</time>
            </div>

            <div class="conteudo-ia w-fit max-w-full rounded-2xl rounded-tl-md border border-zinc-200 bg-white px-4 py-3 shadow-xs dark:border-white/10 dark:bg-white/5">
                {!! $mensagem->conteudo_html !!}
            </div>

            <div class="mt-1.5 flex flex-wrap items-center gap-1.5 px-1 text-[11px] text-zinc-500 dark:text-zinc-400">
                @if ($mensagem->ferramentas)
                    <flux:icon.wrench-screwdriver variant="micro" class="size-3.5" />
                    <span class="sr-only">Ferramentas usadas:</span>
                    @foreach (array_unique($mensagem->ferramentas) as $ferramenta)
                        <span class="rounded-md bg-zinc-100 px-1.5 py-0.5 dark:bg-white/10">{{ \App\Services\Ia\FerramentasDoAssistente::titulos()[$ferramenta] ?? $ferramenta }}</span>
                    @endforeach
                    <span aria-hidden="true">·</span>
                @endif
                <span title="Tokens de entrada: {{ Number::format($mensagem->tokens_entrada) }} ({{ Number::format($mensagem->tokens_cache) }} do cache) · Tokens de saída: {{ Number::format($mensagem->tokens_saida) }}">
                    {{ Number::format($mensagem->tokens_entrada + $mensagem->tokens_saida) }} tokens
                </span>
            </div>
        </div>
    </article>
@else
    <article class="flex justify-end">
        <div class="max-w-[85%] rounded-2xl rounded-tr-md bg-zinc-900 px-4 py-2.5 text-sm leading-6 break-words text-white dark:bg-zinc-100 dark:text-zinc-900">
            {!! $mensagem->conteudo_html !!}
        </div>
    </article>
@endif
