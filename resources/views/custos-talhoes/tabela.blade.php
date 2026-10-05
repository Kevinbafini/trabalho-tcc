{{-- Tabela de custos acumulados por talhão (custos por talhão e detalhes da propriedade). --}}
<flux:table>
    <flux:table.columns>
        <flux:table.column>Talhão</flux:table.column>
        @if ($mostrarPropriedade)
            <flux:table.column>Propriedade</flux:table.column>
        @endif
        <flux:table.column>Cultura</flux:table.column>
        <flux:table.column align="end">Área</flux:table.column>
        <flux:table.column align="end">Atividades</flux:table.column>
        <flux:table.column align="end">Insumos</flux:table.column>
        <flux:table.column align="end">Máquinas</flux:table.column>
        <flux:table.column align="end">Mão de obra</flux:table.column>
        <flux:table.column align="end">Custo total</flux:table.column>
        <flux:table.column align="end">Custo por hectare</flux:table.column>
    </flux:table.columns>

    <flux:table.rows>
        @foreach ($custosPorTalhao as $custo)
            <flux:table.row :key="$custo['talhao']->id">
                <flux:table.cell variant="strong">
                    <flux:link :href="route('talhoes.show', $custo['talhao'])" wire:navigate>{{ $custo['talhao']->nome }}</flux:link>
                </flux:table.cell>
                @if ($mostrarPropriedade)
                    <flux:table.cell>{{ $custo['talhao']->propriedade->nome }}</flux:table.cell>
                @endif
                <flux:table.cell>{{ $custo['talhao']->cultura }}</flux:table.cell>
                <flux:table.cell align="end" class="tabular-nums">{{ Number::format($custo['talhao']->area, 2) }} ha</flux:table.cell>
                <flux:table.cell align="end" class="tabular-nums">{{ $custo['quantidade_atividades'] }}</flux:table.cell>
                <flux:table.cell align="end" class="tabular-nums">{{ Number::currency($custo['custo_insumos']) }}</flux:table.cell>
                <flux:table.cell align="end" class="tabular-nums">{{ Number::currency($custo['custo_maquinas']) }}</flux:table.cell>
                <flux:table.cell align="end" class="tabular-nums">{{ Number::currency($custo['custo_mao_obra']) }}</flux:table.cell>
                <flux:table.cell align="end" variant="strong" class="tabular-nums">{{ Number::currency($custo['custo_total']) }}</flux:table.cell>
                <flux:table.cell align="end" variant="strong" class="tabular-nums">
                    {{ $custo['custo_por_hectare'] !== null ? Number::currency($custo['custo_por_hectare']) : '—' }}
                </flux:table.cell>
            </flux:table.row>
        @endforeach
    </flux:table.rows>
</flux:table>
