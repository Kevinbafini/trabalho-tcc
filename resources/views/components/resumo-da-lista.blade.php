{{--
    Linha acima da lista: quantidade de resultados (com informação extra opcional
    no slot, ex.: área ou custo total) e o alternador Tabela | Cards.
--}}
@props([
    'total',
    'singular',
    'plural',
])

<div class="mb-3 flex flex-wrap items-center justify-between gap-3">
    <flux:text class="text-sm">
        <span class="font-semibold text-zinc-800 tabular-nums dark:text-white">{{ $total }}</span>
        {{ $total === 1 ? $singular : $plural }}
        @if ($slot->isNotEmpty())
            <span class="mx-0.5 text-zinc-400 dark:text-zinc-500">·</span>
            {{ $slot }}
        @endif
    </flux:text>

    <x-alternador-visualizacao />
</div>
