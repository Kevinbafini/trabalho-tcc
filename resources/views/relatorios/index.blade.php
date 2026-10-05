<x-layouts::app title="Relatórios">
    <x-cabecalho-pagina titulo="Relatório de custos" icone="document-chart-bar" descricao="Filtre por propriedade, talhão, safra e período para analisar os custos e gerar o relatório em PDF." />

    <form method="GET" action="{{ route('relatorios.index') }}" class="mb-8">
        <flux:card class="space-y-5">
            <div class="flex items-center gap-2">
                <flux:icon.funnel variant="mini" class="text-zinc-400" />
                <flux:heading>Filtros do relatório</flux:heading>
            </div>

            <div class="grid items-start gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <flux:select name="propriedade_id" label="Propriedade">
                    <flux:select.option value="">Todas</flux:select.option>
                    @foreach ($propriedades as $propriedade)
                        <flux:select.option :value="$propriedade->id" :selected="$filtros['propriedade_id'] === $propriedade->id">{{ $propriedade->nome }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select name="talhao_id" label="Talhão">
                    <flux:select.option value="">Todos</flux:select.option>
                    @foreach ($talhoes as $talhao)
                        <flux:select.option :value="$talhao->id" :selected="$filtros['talhao_id'] === $talhao->id">
                            {{ $talhao->nome }}{{ $propriedades->count() > 1 ? ' ('.$talhao->propriedade->nome.')' : '' }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select name="safra" label="Safra">
                    <flux:select.option value="">Todas</flux:select.option>
                    @foreach ($safras as $safra)
                        <flux:select.option :value="$safra" :selected="$filtros['safra'] === $safra">{{ $safra }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input type="date" name="data_inicio" label="Data inicial" :value="$filtros['data_inicio']" />
                <flux:input type="date" name="data_fim" label="Data final" :value="$filtros['data_fim']" />
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-zinc-200 pt-5 sm:flex-row sm:items-center sm:justify-between dark:border-zinc-700">
                <div>
                    @if (array_filter($filtros))
                        <flux:button :href="route('relatorios.index')" variant="ghost" icon="x-mark" wire:navigate>Limpar filtros</flux:button>
                    @endif
                </div>

                <div class="flex flex-col gap-3 sm:flex-row">
                    <flux:button type="submit" icon="funnel">Aplicar filtros</flux:button>
                    <flux:button
                        type="submit"
                        variant="primary"
                        icon="document-arrow-down"
                        formaction="{{ route('relatorios.pdf') }}"
                        formtarget="_blank"
                    >
                        Gerar relatório em PDF
                    </flux:button>
                </div>
            </div>
        </flux:card>
    </form>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <x-indicador rotulo="Custo com insumos" :valor="Number::currency($resumo['custo_insumos'])" />
        <x-indicador rotulo="Custo com máquinas" :valor="Number::currency($resumo['custo_maquinas'])" />
        <x-indicador rotulo="Custo com mão de obra" :valor="Number::currency($resumo['custo_mao_obra'])" />
        <x-indicador rotulo="Custo total" :valor="Number::currency($resumo['custo_total'])" :detalhe="$resumo['quantidade_atividades'].' atividade(s)'" />
        <x-indicador
            rotulo="Custo por hectare"
            :valor="$resumo['custo_por_hectare'] !== null ? Number::currency($resumo['custo_por_hectare']) : '—'"
            :detalhe="'Custo total ÷ '.Number::format($resumo['area'], 2).' ha (talhões com atividades)'"
        />
    </div>

    @if ($resumo['quantidade_atividades'] > 0)
        <div class="mt-8 grid gap-6 xl:grid-cols-2">
            <x-cartao-grafico
                class="xl:col-span-2"
                titulo="Evolução mensal dos custos"
                descricao="Custo das atividades em cada mês, dividido entre insumos, maquinário e mão de obra."
                funcao="graficoBarrasEmpilhadas"
                :dados="[...$graficoMensal, 'orientacao' => 'vertical']"
                :altura="320"
            />

            <x-cartao-grafico
                titulo="Custo por tipo de operação"
                descricao="Quais operações agrícolas concentraram os custos."
                funcao="graficoBarras"
                :dados="$graficoOperacoes"
                :altura="max(180, count($graficoOperacoes['rotulos']) * 38 + 50)"
            />

            <x-cartao-grafico
                titulo="Composição dos custos"
                descricao="Participação de cada grupo no custo total."
                funcao="graficoComposicao"
                :dados="$graficoComposicao"
                :altura="40"
            >
                <x-legenda-composicao :resumo="$resumo" />
            </x-cartao-grafico>

            <x-cartao-grafico
                class="xl:col-span-2"
                titulo="Custo por hectare dos talhões"
                descricao="Talhões com atividades no período: custo total do talhão ÷ área do talhão."
                funcao="graficoCustoPorHectare"
                :dados="$graficoCustoPorHectare"
                :altura="max(180, count($graficoCustoPorHectare['rotulos']) * 38 + 50)"
            />
        </div>
    @endif

    <div class="mt-8">
        <div class="mb-3 flex items-center justify-between gap-4">
            <flux:heading size="lg">Atividades que formaram o custo</flux:heading>
            @if ($atividades->isNotEmpty())
                <x-alternador-visualizacao />
            @endif
        </div>

        @if ($atividades->isEmpty())
            <x-estado-vazio icone="funnel" titulo="Nenhuma atividade encontrada" descricao="Não há atividades para os filtros selecionados." />
        @else
            @include('atividades.tabela', ['atividades' => $atividades, 'mostrarTalhao' => true, 'alternador' => false])
        @endif
    </div>
</x-layouts::app>
