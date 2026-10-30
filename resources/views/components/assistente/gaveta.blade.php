{{--
    Painel lateral do Assistente IA, disponível em todas as telas pelo botão
    flutuante. O conteúdo é carregado só quando o painel abre (rota
    assistente.painel), para não pesar nas demais telas.
--}}
@if (config('assistente.habilitado') && ! request()->routeIs('assistente.*'))
    <flux:modal.trigger name="assistente-ia">
        <button
            type="button"
            class="fixed end-5 bottom-5 z-40 inline-flex cursor-pointer items-center gap-2 rounded-full bg-zinc-900 px-4 py-3 text-sm font-medium text-white shadow-lg shadow-zinc-900/20 transition hover:bg-zinc-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-zinc-900 dark:bg-white dark:text-zinc-900 dark:shadow-black/40 dark:hover:bg-zinc-200"
            aria-label="Abrir o Assistente IA"
        >
            <flux:icon.sparkles variant="mini" />
            <span class="hidden sm:inline">Assistente IA</span>
        </button>
    </flux:modal.trigger>

    <flux:modal name="assistente-ia" variant="flyout" class="w-full p-0! sm:max-w-md md:max-w-lg">
        <div
            x-data="painelDoAssistente(@js(route('assistente.painel')))"
            x-on:modal-show.document="if ($event.detail.name === 'assistente-ia') carregar()"
            class="flex h-dvh flex-col"
        >
            <div x-ref="conteudo" class="flex min-h-0 flex-1 flex-col">
                <div class="flex flex-1 items-center justify-center gap-2 text-sm text-zinc-500">
                    <flux:icon.loading variant="mini" />
                    Abrindo o assistente…
                </div>
            </div>
        </div>
    </flux:modal>
@endif
