<x-layouts::app title="Custos por talhão">
    <x-cabecalho-pagina titulo="Custos por talhão" icone="banknotes" descricao="Custos acumulados das atividades de cada talhão e o custo por hectare (custo total ÷ área do talhão).">
        <x-slot:acoes>
            <flux:button
                :href="route('relatorios.pdf', array_filter($filtros))"
                target="_blank"
                icon="document-arrow-down"
            >
                Exportar PDF
            </flux:button>
        </x-slot:acoes>
    </x-cabecalho-pagina>

    <form method="GET" class="mb-6 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
        @if ($propriedades->count() > 1)
            <div class="sm:w-64">
                <flux:select name="propriedade_id" aria-label="Propriedade" onchange="this.form.submit()">
                    <flux:select.option value="">Todas as propriedades</flux:select.option>
                    @foreach ($propriedades as $propriedade)
                        <flux:select.option :value="$propriedade->id" :selected="$filtros['propriedade_id'] === $propriedade->id">{{ $propriedade->nome }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        @endif

        <div class="sm:w-56">
            <flux:select name="safra" aria-label="Safra" onchange="this.form.submit()">
                <flux:select.option value="">Todas as safras</flux:select.option>
                @foreach ($safras as $safra)
                    <flux:select.option :value="$safra" :selected="$filtros['safra'] === $safra">Safra {{ $safra }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        @if (array_filter($filtros))
            <flux:button :href="route('custos-talhoes.index')" variant="ghost" icon="x-mark" wire:navigate>Limpar filtros</flux:button>
        @endif
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

        @if ($resumo['quantidade_atividades'] > 0)
            <div class="mb-8 grid gap-6 xl:grid-cols-2">
                <x-cartao-grafico
                    class="xl:col-span-2"
                    titulo="Custo total por talhão"
                    descricao="Quanto cada talhão custou, dividido entre insumos, maquinário e mão de obra."
                    funcao="graficoBarrasEmpilhadas"
                    :dados="[...$graficoCustoTotalPorTalhao, 'orientacao' => 'horizontal']"
                    :altura="count($graficoCustoTotalPorTalhao['rotulos']) * 44 + 90"
                />

                <x-cartao-grafico
                    titulo="Custo por hectare dos talhões"
                    descricao="Custo total do talhão ÷ área do talhão."
                    funcao="graficoCustoPorHectare"
                    :dados="$graficoCustoPorHectare"
                    :altura="max(180, count($graficoCustoPorHectare['rotulos']) * 38 + 50)"
                />

                <x-cartao-grafico
                    titulo="Custo por hectare por cultura"
                    descricao="Custo dos talhões de cada cultura ÷ área desses talhões."
                    funcao="graficoCustoPorHectare"
                    :dados="$graficoCustoPorCultura"
                    :altura="max(180, count($graficoCustoPorCultura['rotulos']) * 38 + 50)"
                />

                <x-cartao-grafico
                    class="xl:col-span-2"
                    titulo="Composição dos custos"
                    descricao="Participação de cada grupo no custo total dos talhões."
                    funcao="graficoComposicao"
                    :dados="$graficoComposicao"
                    :altura="40"
                >
                    <x-legenda-composicao :resumo="$resumo" />
                </x-cartao-grafico>
            </div>
        @endif

        <div class="mb-3 flex items-center justify-between gap-4">
            <flux:heading size="lg">Detalhamento por talhão</flux:heading>
            <x-alternador-visualizacao />
        </div>

        @include('custos-talhoes.tabela', ['custosPorTalhao' => $custosPorTalhao, 'mostrarPropriedade' => $propriedades->count() > 1, 'alternador' => false])

        <flux:text class="mt-3 text-sm">Clique no nome do talhão para ver as atividades que formam o custo.</flux:text>
    @endif
</x-layouts::app>
