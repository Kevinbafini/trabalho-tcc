{{--
    Exibe a mesma lista em dois formatos:

    - Telas menores que 1280px: sempre cards.
    - Telas a partir de 1280px (breakpoint xl): tabela ou cards, conforme o
      modo escolhido no alternador. A escolha vale para todas as listas e fica
      salva no navegador (veja resources/js/visualizacao-listas.js).

    Slots: $tabela e $cartoes.
    Props: :paginacao="$paginator" (opcional) e :alternador="false" quando a página já
    exibe o <x-alternador-visualizacao /> em outro lugar (ex.: na linha do título).
--}}
@props([
    'paginacao' => null,
    'alternador' => true,
])

<div
    x-data="visualizacaoDasListas()"
    x-on:visualizacao-alterada.window="modo = $event.detail"
    {{ $attributes }}
>
    @if ($alternador)
        <div class="mb-3 hidden justify-end xl:flex">
            <x-alternador-visualizacao />
        </div>
    @endif

    {{-- Tabela: só em telas grandes e no modo "tabela". --}}
    <div class="hidden rounded-xl border border-zinc-200 bg-white px-4 shadow-xs xl:block dark:border-zinc-700 dark:bg-white/5" x-show="modo === 'tabela'">
        {{ $tabela }}
    </div>

    {{-- Cards: sempre em telas menores; em telas grandes, só no modo "cartoes". --}}
    <div
        class="grid gap-3 sm:grid-cols-2 xl:hidden"
        x-bind:class="modo === 'cartoes' && 'xl:grid! xl:grid-cols-3 2xl:grid-cols-4'"
    >
        {{ $cartoes }}
    </div>

    @if ($paginacao instanceof \Illuminate\Contracts\Pagination\Paginator)
        <flux:pagination :paginator="$paginacao" class="mt-4" />
    @endif
</div>
