{{-- Campo de busca: dispara a busca enquanto o usuário digita (com pequeno atraso). --}}
@props([
    'placeholder' => 'Buscar...',
    'valor' => '',
])

<div class="min-w-0 sm:flex-1">
    <flux:input
        name="busca"
        :value="$valor"
        icon="magnifying-glass"
        :placeholder="$placeholder"
        aria-label="Buscar"
        autocomplete="off"
        clearable
        x-on:input.debounce.350ms="buscar()"
        {{ $attributes }}
    />
</div>
