{{-- Filtro (select) da barra de busca: aplica assim que o valor muda. --}}
@props([
    'nome',
    'rotulo',
])

<div class="sm:w-56">
    <flux:select :name="$nome" :aria-label="$rotulo" x-on:change="buscar()" {{ $attributes }}>
        {{ $slot }}
    </flux:select>
</div>
