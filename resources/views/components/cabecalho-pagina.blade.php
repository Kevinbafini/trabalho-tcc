{{-- Título da página, descrição opcional, link "Voltar" opcional e área de botões de ação. --}}
@props([
    'titulo',
    'descricao' => null,
    'voltar' => null,
])

<div class="mb-6">
    @if ($voltar)
        <flux:button :href="$voltar" variant="ghost" size="sm" icon="arrow-left" class="-ms-2 mb-2" wire:navigate>
            Voltar
        </flux:button>
    @endif

    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ $titulo }}</flux:heading>

            @if ($descricao)
                <flux:text class="mt-1">{{ $descricao }}</flux:text>
            @endif
        </div>

        @isset($acoes)
            <div class="flex flex-wrap gap-2">
                {{ $acoes }}
            </div>
        @endisset
    </div>
</div>
