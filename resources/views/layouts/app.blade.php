{{--
    Layout das telas do sistema. Com tela-cheia (ex.: Assistente IA), a área
    principal perde as margens internas e a tela ocupa toda a altura; as
    mensagens de retorno ficam a cargo da própria tela.
--}}
@props([
    'title' => null,
    'telaCheia' => false,
])

<x-layouts::app.sidebar :title="$title">
    <flux:main @class(['relative isolate', 'p-0! lg:p-0!' => $telaCheia, 'bg-fundo dark:bg-transparent' => ! $telaCheia])>
        @unless ($telaCheia)
            {{-- Detalhe decorativo no topo da tela: degradê e pontilhado verdes que somem para baixo. --}}
            <div class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-72 overflow-hidden" aria-hidden="true">
                <div class="absolute inset-0 bg-linear-to-b from-primary/10 to-transparent dark:from-secondary/25"></div>
                <div class="absolute inset-0 bg-[radial-gradient(var(--color-primary)_1px,transparent_1.5px)] [background-size:22px_22px] opacity-15 [mask-image:linear-gradient(to_bottom,black,transparent)] dark:bg-[radial-gradient(white_1px,transparent_1.5px)] dark:opacity-[0.07]"></div>
            </div>

            <x-mensagens />
        @endunless

        {{ $slot }}
    </flux:main>

    <x-assistente.gaveta />
</x-layouts::app.sidebar>
