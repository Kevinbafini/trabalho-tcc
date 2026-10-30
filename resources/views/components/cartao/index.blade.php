{{--
    Card de um registro (listas em modo "cards").

    Props: titulo, link, subtitulo, icone (avatar com ícone do módulo).
    Slots: $avatar (substitui o ícone, ex.: iniciais ou ícone da operação),
           $selo (ex.: situação), $destaque (valor principal à direita),
           conteúdo com <x-cartao.campo> e $acoes (botões).
--}}
@props([
    'titulo',
    'link' => null,
    'subtitulo' => null,
    'icone' => null,
])

<div {{ $attributes->class('group relative flex min-w-0 flex-col overflow-hidden rounded-xl border border-zinc-200 bg-white p-4 shadow-xs transition duration-200 hover:-translate-y-0.5 hover:border-primary/25 hover:shadow-md hover:shadow-primary/5 dark:border-zinc-700 dark:bg-white/5 dark:hover:border-secondary/60') }}>
    {{-- Linha nos verdes do logo, que aparece ao passar o mouse --}}
    <span class="absolute inset-x-0 top-0 h-0.5 bg-linear-to-r from-primary via-secondary to-accent-green opacity-0 transition group-hover:opacity-100" aria-hidden="true"></span>

    <div class="flex-1">
        <div class="flex items-start gap-3">
            @isset($avatar)
                {{ $avatar }}
            @elseif ($icone)
                <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary/8 text-primary dark:bg-secondary/30 dark:text-accent-content" aria-hidden="true">
                    <flux:icon :name="$icone" variant="mini" />
                </span>
            @endisset

            <div class="min-w-0 flex-1">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        @if ($link)
                            <flux:link :href="$link" class="font-medium" wire:navigate>{{ $titulo }}</flux:link>
                        @else
                            <p class="truncate font-medium text-zinc-800 dark:text-white">{{ $titulo }}</p>
                        @endif

                        @if ($subtitulo)
                            <flux:text class="mt-0.5 truncate text-sm">{{ $subtitulo }}</flux:text>
                        @endif
                    </div>

                    @isset($selo)
                        <div class="shrink-0">{{ $selo }}</div>
                    @endisset

                    @isset($destaque)
                        <div class="shrink-0 text-end">{{ $destaque }}</div>
                    @endisset
                </div>
            </div>
        </div>

        @if ($slot->isNotEmpty())
            <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                {{ $slot }}
            </dl>
        @endif
    </div>

    @isset($acoes)
        <div class="mt-4 flex flex-wrap justify-end gap-1 border-t border-zinc-100 pt-3 dark:border-zinc-700">
            {{ $acoes }}
        </div>
    @endisset
</div>
