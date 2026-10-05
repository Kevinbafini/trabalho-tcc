<x-layouts::app title="Relatórios">
    <x-cabecalho-pagina titulo="Relatório de custos" icone="document-chart-bar" descricao="Filtre por propriedade, talhão, safra e período para ver os custos e as atividades que os formaram." />

    <form method="GET" class="mb-6">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
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

        <div class="mt-4 flex gap-2">
            <flux:button type="submit" variant="primary" icon="funnel">Gerar relatório</flux:button>
            @if (array_filter($filtros))
                <flux:button :href="route('relatorios.index')" variant="ghost" wire:navigate>Limpar filtros</flux:button>
            @endif
        </div>
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
