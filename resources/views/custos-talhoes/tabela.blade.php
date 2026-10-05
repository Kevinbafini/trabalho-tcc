{{-- Custos acumulados por talhão (custos por talhão e detalhes da propriedade): tabela em telas grandes e cards em telas menores. --}}
<x-lista-responsiva :alternador="$alternador ?? true">
    <x-slot:tabela>
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
    </x-slot:tabela>

    <x-slot:cartoes>
        @foreach ($custosPorTalhao as $custo)
            <x-cartao
                :titulo="$custo['talhao']->nome"
                :link="route('talhoes.show', $custo['talhao'])"
                :subtitulo="($mostrarPropriedade ? $custo['talhao']->propriedade->nome.' · ' : '').$custo['talhao']->cultura.' · '.Number::format($custo['talhao']->area, 2).' ha'"
            >
                <x-cartao.campo rotulo="Atividades">{{ $custo['quantidade_atividades'] }}</x-cartao.campo>
                <x-cartao.campo rotulo="Insumos">{{ Number::currency($custo['custo_insumos']) }}</x-cartao.campo>
                <x-cartao.campo rotulo="Máquinas">{{ Number::currency($custo['custo_maquinas']) }}</x-cartao.campo>
                <x-cartao.campo rotulo="Mão de obra">{{ Number::currency($custo['custo_mao_obra']) }}</x-cartao.campo>
                <x-cartao.campo rotulo="Custo total" :destaque="true">{{ Number::currency($custo['custo_total']) }}</x-cartao.campo>
                <x-cartao.campo rotulo="Custo por hectare" :destaque="true">
                    {{ $custo['custo_por_hectare'] !== null ? Number::currency($custo['custo_por_hectare']) : '—' }}
                </x-cartao.campo>
            </x-cartao>
        @endforeach
    </x-slot:cartoes>
</x-lista-responsiva>
