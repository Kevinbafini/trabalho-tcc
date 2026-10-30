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
    <flux:main @class(['p-0! lg:p-0!' => $telaCheia])>
        @unless ($telaCheia)
            <x-mensagens />
        @endunless

        {{ $slot }}
    </flux:main>

    <x-assistente.gaveta />
</x-layouts::app.sidebar>
