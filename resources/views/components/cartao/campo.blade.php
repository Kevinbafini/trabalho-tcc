{{-- Par "rótulo: valor" dentro de um <x-cartao>. Use :destaque="true" para valores principais (ex.: custo total). --}}
@props([
    'rotulo',
    'destaque' => false,
])

<div>
    <dt class="text-xs text-zinc-500 dark:text-zinc-400">{{ $rotulo }}</dt>
    <dd @class([
        'tabular-nums',
        'font-semibold text-zinc-900 dark:text-white' => $destaque,
        'text-zinc-700 dark:text-zinc-300' => ! $destaque,
    ])>{{ $slot }}</dd>
</div>
