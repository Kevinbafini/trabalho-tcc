<x-layouts::app title="Custos por talhão">
    <x-cabecalho-pagina titulo="Custos por talhão" icone="banknotes" descricao="Custos acumulados das atividades de cada talhão e o custo por hectare (custo total ÷ área do talhão)." />

    <form method="GET" class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4 lg:items-end">
        @if ($propriedades->count() > 1)
            <flux:select name="propriedade_id" label="Propriedade">
                <flux:select.option value="">Todas</flux:select.option>
                @foreach ($propriedades as $propriedade)
                    <flux:select.option :value="$propriedade->id" :selected="$filtros['propriedade_id'] === $propriedade->id">{{ $propriedade->nome }}</flux:select.option>
                @endforeach
            </flux:select>
        @endif

        <flux:select name="safra" label="Safra">
            <flux:select.option value="">Todas</flux:select.option>
            @foreach ($safras as $safra)
                <flux:select.option :value="$safra" :selected="$filtros['safra'] === $safra">{{ $safra }}</flux:select.option>
            @endforeach
        </flux:select>

        <div class="flex gap-2">
            <flux:button type="submit" icon="funnel">Filtrar</flux:button>
            @if (array_filter($filtros))
                <flux:button :href="route('custos-talhoes.index')" variant="ghost" wire:navigate>Limpar</flux:button>
            @endif
        </div>
    </form>

    @if ($custosPorTalhao->isEmpty())
        <x-estado-vazio icone="map" titulo="Nenhum talhão cadastrado" descricao="Cadastre talhões e registre atividades para acompanhar os custos.">
            <flux:button :href="route('talhoes.create')" variant="primary" icon="plus" wire:navigate>Cadastrar talhão</flux:button>
        </x-estado-vazio>
    @else
        <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <x-indicador rotulo="Insumos" :valor="Number::currency($resumo['custo_insumos'])" />
            <x-indicador rotulo="Maquinário" :valor="Number::currency($resumo['custo_maquinas'])" />
            <x-indicador rotulo="Mão de obra" :valor="Number::currency($resumo['custo_mao_obra'])" />
            <x-indicador rotulo="Custo total" :valor="Number::currency($resumo['custo_total'])" />
            <x-indicador
                rotulo="Custo médio por hectare"
                :valor="$resumo['custo_por_hectare'] !== null ? Number::currency($resumo['custo_por_hectare']) : '—'"
                :detalhe="'Sobre '.Number::format($resumo['area'], 2).' ha'"
            />
        </div>

        @include('custos-talhoes.tabela', ['custosPorTalhao' => $custosPorTalhao, 'mostrarPropriedade' => $propriedades->count() > 1])

        <flux:text class="mt-3 text-sm">Clique no nome do talhão para ver as atividades que formam o custo.</flux:text>
    @endif
</x-layouts::app>
