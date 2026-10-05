{{-- Barra de ações no final dos formulários: Cancelar e Salvar (botão principal à direita). --}}
@props([
    'cancelar',
    'textoSalvar' => 'Salvar',
])

<div class="flex flex-col-reverse gap-3 border-t border-zinc-200 pt-6 sm:flex-row sm:items-center sm:justify-end dark:border-zinc-700">
    <flux:button :href="$cancelar" variant="ghost" wire:navigate>Cancelar</flux:button>
    <flux:button type="submit" variant="primary" icon="check">{{ $textoSalvar }}</flux:button>
</div>
