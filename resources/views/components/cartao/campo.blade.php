{{-- Par "rótulo: valor" dentro de um <x-cartao>, com ícone opcional. Use :destaque="true" para valores principais. --}}
@props([
    'rotulo',
    'icone' => null,
    'destaque' => false,
])

<div class="min-w-0">
    <dt class="flex items-center gap-1 text-xs text-zinc-500 dark:text-zinc-400">
        @if ($icone)
            <flux:icon :name="$icone" variant="micro" class="size-3.5 shrink-0 text-zinc-400 dark:text-zinc-500" />
        @endif
        {{ $rotulo }}
    </dt>
    <dd @class([
        'mt-0.5 truncate tabular-nums',
        'font-semibold text-zinc-900 dark:text-white' => $destaque,
        'text-zinc-700 dark:text-zinc-300' => ! $destaque,
    ])>{{ $slot }}</dd>
</div>
