{{--
    Listagem com busca em tempo real (veja resources/js/busca-tempo-real.js).

    Slot $filtros: campo de busca e filtros (sem botão: a busca é automática).
    Slot padrão: os resultados, que devem estar dentro de @fragment('resultados')
    na view, para o controller conseguir devolver só esse trecho.
--}}
<div x-data="buscaEmTempoReal()" {{ $attributes }}>
    <form
        x-ref="formulario"
        x-on:submit.prevent="buscar()"
        role="search"
        class="mb-5 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center"
    >
        {{ $filtros }}
    </form>

    <div
        x-ref="resultados"
        aria-live="polite"
        class="transition-opacity duration-150"
        x-bind:class="carregando && 'pointer-events-none opacity-50'"
    >
        {{ $slot }}
    </div>
</div>
