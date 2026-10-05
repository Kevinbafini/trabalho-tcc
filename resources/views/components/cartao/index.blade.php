{{--
    Card de um registro, usado no lugar da tabela em telas menores.

    Props: titulo, link (opcional), subtitulo (opcional).
    Slots: $selo (ex.: situação), conteúdo com <x-cartao.campo> e $acoes (botões).
--}}
@props([
    'titulo',
    'link' => null,
    'subtitulo' => null,
])

<div {{ $attributes->class('flex flex-col rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-white/5') }}>
    <div class="flex-1">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                @if ($link)
                    <flux:link :href="$link" class="font-medium" wire:navigate>{{ $titulo }}</flux:link>
                @else
                    <p class="font-medium text-zinc-800 dark:text-white">{{ $titulo }}</p>
                @endif

                @if ($subtitulo)
                    <flux:text class="mt-0.5 text-sm">{{ $subtitulo }}</flux:text>
                @endif
            </div>

            @isset($selo)
                <div class="shrink-0">{{ $selo }}</div>
            @endisset
        </div>

        @if ($slot->isNotEmpty())
            <dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                {{ $slot }}
            </dl>
        @endif
    </div>

    @isset($acoes)
        <div class="mt-3 flex flex-wrap justify-end gap-1 border-t border-zinc-100 pt-3 dark:border-zinc-700">
            {{ $acoes }}
        </div>
    @endisset
</div>
