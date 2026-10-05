{{-- Botão "Excluir" que abre uma janela de confirmação antes de enviar a exclusão. --}}
@props([
    'acao',
    'mensagem',
    'nome',
])

<flux:modal.trigger :name="'excluir-'.$nome">
    <flux:button size="sm" variant="ghost" icon="trash" {{ $attributes }}>Excluir</flux:button>
</flux:modal.trigger>

<flux:modal :name="'excluir-'.$nome" class="max-w-md">
    <form method="POST" action="{{ $acao }}" class="space-y-6">
        @csrf
        @method('DELETE')

        <div>
            <flux:heading size="lg">Confirmar exclusão</flux:heading>
            <flux:text class="mt-2 whitespace-normal">{{ $mensagem }}</flux:text>
        </div>

        <div class="flex justify-end gap-2">
            <flux:modal.close>
                <flux:button variant="ghost">Cancelar</flux:button>
            </flux:modal.close>

            <flux:button type="submit" variant="danger">Excluir</flux:button>
        </div>
    </form>
</flux:modal>
