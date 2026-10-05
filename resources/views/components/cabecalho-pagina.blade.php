{{-- Título da página com ícone opcional, descrição, link "Voltar" e área de botões de ação. --}}
@props([
    'titulo',
    'descricao' => null,
    'voltar' => null,
    'icone' => null,
])

<div class="mb-6">
    @if ($voltar)
        <flux:button :href="$voltar" variant="ghost" size="sm" icon="arrow-left" class="-ms-2 mb-2" wire:navigate>
            Voltar
        </flux:button>
    @endif

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-4">
            @if ($icone)
                <div class="hidden size-12 shrink-0 items-center justify-center rounded-xl border border-zinc-200 bg-white shadow-xs sm:flex dark:border-white/10 dark:bg-white/5">
                    <flux:icon :name="$icone" class="size-6 text-zinc-600 dark:text-zinc-300" />
                </div>
            @endif

            <div>
                <flux:heading size="xl" level="1">{{ $titulo }}</flux:heading>

                @if ($descricao)
                    <flux:text class="mt-1">{{ $descricao }}</flux:text>
                @endif
            </div>
        </div>

        @isset($acoes)
            <div class="flex flex-wrap gap-2">
                {{ $acoes }}
            </div>
        @endisset
    </div>
</div>
