{{-- Cartão de indicador numérico (dashboard, relatórios e detalhes). --}}
@props([
    'rotulo',
    'valor',
    'detalhe' => null,
])

<flux:card {{ $attributes->class('p-4!') }}>
    <flux:text class="text-sm">{{ $rotulo }}</flux:text>
    <flux:heading size="xl" class="mt-1 tabular-nums">{{ $valor }}</flux:heading>

    @if ($detalhe)
        <flux:text class="mt-1 text-xs">{{ $detalhe }}</flux:text>
    @endif
</flux:card>
