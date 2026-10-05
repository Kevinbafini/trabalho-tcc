{{-- Botões padrão dos formulários. --}}
<div class="flex flex-wrap items-center gap-3 pt-2">
    <flux:button type="submit" variant="primary" icon="check">{{ $textoSalvar ?? 'Salvar' }}</flux:button>
    <flux:button :href="$cancelar" variant="ghost" wire:navigate>Cancelar</flux:button>
</div>
