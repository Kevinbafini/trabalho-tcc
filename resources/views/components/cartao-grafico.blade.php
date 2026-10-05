{{--
    Card com um gráfico (Chart.js). A função JavaScript define o tipo de gráfico
    (veja resources/js/graficos.js) e os dados vêm do GraficosCustoService.
    O slot (opcional) aparece abaixo do gráfico, ex.: legenda com valores.
--}}
@props([
    'titulo',
    'descricao' => null,
    'funcao',
    'dados',
    'altura' => 280,
    'rotulo' => null,
])

<flux:card {{ $attributes->class('flex min-w-0 flex-col gap-4') }}>
    <div>
        <flux:heading size="lg">{{ $titulo }}</flux:heading>

        @if ($descricao)
            <flux:text class="mt-0.5 text-sm">{{ $descricao }}</flux:text>
        @endif
    </div>

    <div class="relative" style="height: {{ $altura }}px">
        <canvas wire:ignore x-data="{{ $funcao }}(@js($dados))" role="img" aria-label="{{ $rotulo ?? $titulo }}"></canvas>
    </div>

    {{ $slot }}
</flux:card>
