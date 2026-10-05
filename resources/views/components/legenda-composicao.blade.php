{{-- Legenda do gráfico de composição: cor, grupo, valor e participação no custo total. --}}
@props([
    'resumo',
])

<ul {{ $attributes->class('space-y-2 text-sm') }}>
    @foreach ([
        ['Insumos', $resumo['custo_insumos'], 'bg-[#2a78d6] dark:bg-[#3987e5]'],
        ['Maquinário', $resumo['custo_maquinas'], 'bg-[#eb6834] dark:bg-[#d95926]'],
        ['Mão de obra', $resumo['custo_mao_obra'], 'bg-[#1baf7a] dark:bg-[#199e70]'],
    ] as [$rotulo, $valor, $cor])
        <li class="flex items-center justify-between gap-4">
            <span class="flex items-center gap-2 text-zinc-600 dark:text-zinc-300">
                <span class="size-3 rounded-sm {{ $cor }}"></span>
                {{ $rotulo }}
            </span>
            <span class="text-zinc-800 tabular-nums dark:text-white">
                {{ Number::currency($valor) }}
                <span class="text-zinc-500 dark:text-zinc-400">({{ Number::percentage($resumo['custo_total'] > 0 ? $valor / $resumo['custo_total'] * 100 : 0, 1) }})</span>
            </span>
        </li>
    @endforeach

    <li class="flex items-center justify-between gap-4 border-t border-zinc-200 pt-2 font-medium dark:border-zinc-700">
        <span class="text-zinc-800 dark:text-white">Total</span>
        <span class="text-zinc-800 tabular-nums dark:text-white">{{ Number::currency($resumo['custo_total']) }}</span>
    </li>
</ul>
