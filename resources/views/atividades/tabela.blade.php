{{-- Tabela de atividades com seus custos (atividades, talhão e relatórios). --}}
<flux:table :paginate="$atividades instanceof \Illuminate\Contracts\Pagination\Paginator ? $atividades : null">
    <flux:table.columns>
        <flux:table.column>Data</flux:table.column>
        @if ($mostrarTalhao)
            <flux:table.column>Talhão</flux:table.column>
        @endif
        <flux:table.column>Operação</flux:table.column>
        <flux:table.column>Safra</flux:table.column>
        <flux:table.column align="end">Insumos</flux:table.column>
        <flux:table.column align="end">Máquinas</flux:table.column>
        <flux:table.column align="end">Mão de obra</flux:table.column>
        <flux:table.column align="end">Custo total</flux:table.column>
        <flux:table.column align="end"><span class="sr-only">Ações</span></flux:table.column>
    </flux:table.columns>

    <flux:table.rows>
        @foreach ($atividades as $atividade)
            <flux:table.row :key="$atividade->id">
                <flux:table.cell class="tabular-nums">{{ $atividade->data->format('d/m/Y') }}</flux:table.cell>
                @if ($mostrarTalhao)
                    <flux:table.cell>{{ $atividade->talhao->nome }}</flux:table.cell>
                @endif
                <flux:table.cell variant="strong">
                    <flux:link :href="route('atividades.show', $atividade)" wire:navigate>{{ $atividade->tipo->rotulo() }}</flux:link>
                </flux:table.cell>
                <flux:table.cell>{{ $atividade->safra }}</flux:table.cell>
                <flux:table.cell align="end" class="tabular-nums">{{ Number::currency($atividade->custo_insumos) }}</flux:table.cell>
                <flux:table.cell align="end" class="tabular-nums">{{ Number::currency($atividade->custo_maquinas) }}</flux:table.cell>
                <flux:table.cell align="end" class="tabular-nums">{{ Number::currency($atividade->custo_mao_obra) }}</flux:table.cell>
                <flux:table.cell align="end" variant="strong" class="tabular-nums">{{ Number::currency($atividade->custo_total) }}</flux:table.cell>
                <flux:table.cell align="end">
                    <flux:button size="sm" variant="ghost" icon="eye" :href="route('atividades.show', $atividade)" wire:navigate>Ver</flux:button>
                </flux:table.cell>
            </flux:table.row>
        @endforeach
    </flux:table.rows>
</flux:table>
