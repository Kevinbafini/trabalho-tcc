{{--
    Logo do SCAA: colorido no modo claro e branco no modo escuro.
    A altura vem da classe recebida (ex.: <x-logo-scaa class="h-12" />); a largura acompanha.
--}}
<span {{ $attributes->class('inline-flex shrink-0') }}>
    <img src="{{ asset('images/logo-scaa.png') }}" alt="SCAA — Sistema de Controle de Atividades Agrícolas" class="h-full w-auto dark:hidden">
    <img src="{{ asset('images/logo-scaa-branca.png') }}" alt="SCAA — Sistema de Controle de Atividades Agrícolas" class="hidden h-full w-auto dark:block">
</span>
