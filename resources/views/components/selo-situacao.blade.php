{{-- Selo da situação (talhões, recursos) com cor e ícone definidos no enum. --}}
@props([
    'status',
])

<flux:badge size="sm" :color="$status->cor()" :icon="$status->icone()" {{ $attributes }}>{{ $status->rotulo() }}</flux:badge>
