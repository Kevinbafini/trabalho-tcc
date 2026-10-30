{{-- Cartão de indicador numérico (dashboard, relatórios e detalhes). --}}
@props([
    'rotulo',
    'valor',
    'detalhe' => null,
])

<flux:card {{ $attributes->class('relative overflow-hidden p-4!') }}>
    {{-- Faixa nos tons de verde do logo --}}
    <span class="absolute inset-x-0 top-0 h-1 bg-linear-to-r from-primary via-secondary to-accent-green" aria-hidden="true"></span>

    <flux:text class="text-sm">{{ $rotulo }}</flux:text>
    <flux:heading size="xl" class="mt-1 text-primary tabular-nums dark:text-white">{{ $valor }}</flux:heading>

    @if ($detalhe)
        <flux:text class="mt-1 text-xs">{{ $detalhe }}</flux:text>
    @endif
</flux:card>
