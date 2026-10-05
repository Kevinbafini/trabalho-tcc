{{--
    Alternador "Tabela | Cards" das listas. Aparece somente em telas a partir
    de 1280px (em telas menores as listas sempre usam cards). A escolha vale
    para todas as listas e fica salva no navegador.
--}}
<div
    x-data="visualizacaoDasListas()"
    x-on:visualizacao-alterada.window="modo = $event.detail"
    role="group"
    aria-label="Modo de visualização das listas"
    {{ $attributes->class('hidden rounded-lg border border-zinc-200 p-0.5 xl:inline-flex dark:border-zinc-700') }}
>
    <button
        type="button"
        x-on:click="alterar('tabela')"
        x-bind:aria-pressed="modo === 'tabela'"
        class="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1 text-sm text-zinc-500 hover:text-zinc-800 aria-pressed:bg-zinc-100 aria-pressed:text-zinc-900 dark:text-zinc-400 dark:hover:text-white dark:aria-pressed:bg-white/10 dark:aria-pressed:text-white"
    >
        <flux:icon.table-cells variant="micro" />
        Tabela
    </button>

    <button
        type="button"
        x-on:click="alterar('cartoes')"
        x-bind:aria-pressed="modo === 'cartoes'"
        class="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1 text-sm text-zinc-500 hover:text-zinc-800 aria-pressed:bg-zinc-100 aria-pressed:text-zinc-900 dark:text-zinc-400 dark:hover:text-white dark:aria-pressed:bg-white/10 dark:aria-pressed:text-white"
    >
        <flux:icon.squares-2x2 variant="micro" />
        Cards
    </button>
</div>
