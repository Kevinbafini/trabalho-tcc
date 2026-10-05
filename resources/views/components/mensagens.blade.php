{{-- Mensagens de retorno após salvar, excluir ou quando uma ação não é permitida. --}}
@if (session('sucesso'))
    <div x-data="{ visivel: true }" x-show="visivel" class="mb-6" role="status">
        <flux:callout variant="success" icon="check-circle" :heading="session('sucesso')">
            <x-slot name="controls">
                <flux:button icon="x-mark" variant="ghost" size="sm" x-on:click="visivel = false" aria-label="Fechar mensagem" />
            </x-slot>
        </flux:callout>
    </div>
@endif

@if (session('erro'))
    <div x-data="{ visivel: true }" x-show="visivel" class="mb-6" role="alert">
        <flux:callout variant="danger" icon="exclamation-triangle" :heading="session('erro')">
            <x-slot name="controls">
                <flux:button icon="x-mark" variant="ghost" size="sm" x-on:click="visivel = false" aria-label="Fechar mensagem" />
            </x-slot>
        </flux:callout>
    </div>
@endif
