{{--
    Painel lateral do Assistente IA, disponível em todas as telas pelo botão
    flutuante. O conteúdo é carregado só quando o painel abre (rota
    assistente.painel), para não pesar nas demais telas.
--}}
@if (config('assistente.habilitado') && ! request()->routeIs('assistente.*'))
    <flux:modal.trigger name="assistente-ia">
        <button
            type="button"
            class="fixed end-5 bottom-5 z-40 inline-flex cursor-pointer items-center gap-2 rounded-full bg-accent px-4 py-3 text-sm font-medium text-accent-foreground shadow-lg shadow-primary/25 transition hover:brightness-110 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent dark:shadow-black/40"
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
