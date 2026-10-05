{{--
    Exibe a mesma lista em dois formatos, conforme o tamanho da tela:
    cards em telas menores e tabela a partir de 1280px (breakpoint xl).

    Slots: $tabela (conteúdo para telas grandes) e $cartoes (telas pequenas).
    Paginação opcional: :paginacao="$paginator" (exibida uma única vez, abaixo).
--}}
@props([
    'paginacao' => null,
])

<div {{ $attributes }}>
    <div class="hidden xl:block">
        {{ $tabela }}
    </div>

    <div class="grid gap-3 sm:grid-cols-2 xl:hidden">
        {{ $cartoes }}
    </div>

    @if ($paginacao instanceof \Illuminate\Contracts\Pagination\Paginator)
        <flux:pagination :paginator="$paginacao" class="mt-4" />
    @endif
</div>
