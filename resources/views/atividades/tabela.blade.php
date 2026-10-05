{{-- Atividades com seus custos (dashboard, atividades, talhão e relatórios): tabela em telas grandes e cards em telas menores. --}}
<x-lista-responsiva :paginacao="$atividades" :alternador="$alternador ?? true">
    <x-slot:tabela>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Operação</flux:table.column>
                <flux:table.column>Data</flux:table.column>
                @if ($mostrarTalhao)
                    <flux:table.column>Talhão</flux:table.column>
                @endif
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
                        <flux:table.cell variant="strong">
                            <div class="flex items-center gap-3">
                                <x-icone-operacao :tipo="$atividade->tipo" tamanho="sm" />
                                <flux:link :href="route('atividades.show', $atividade)" wire:navigate>{{ $atividade->tipo->rotulo() }}</flux:link>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell class="tabular-nums">{{ $atividade->data->format('d/m/Y') }}</flux:table.cell>
                        @if ($mostrarTalhao)
                            <flux:table.cell>{{ $atividade->talhao->nome }}</flux:table.cell>
                        @endif
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
    </x-slot:tabela>

    <x-slot:cartoes>
        @foreach ($atividades as $atividade)
            <x-cartao :titulo="$atividade->tipo->rotulo()" :link="route('atividades.show', $atividade)" :subtitulo="$atividade->descricao">
                <x-slot:avatar>
                    <x-icone-operacao :tipo="$atividade->tipo" />
                </x-slot:avatar>
                <x-slot:destaque>
                    <p class="font-semibold text-zinc-900 tabular-nums dark:text-white">{{ Number::currency($atividade->custo_total) }}</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">custo total</p>
                </x-slot:destaque>

                <x-cartao.campo rotulo="Data" icone="calendar-days">{{ $atividade->data->format('d/m/Y') }}</x-cartao.campo>
                @if ($mostrarTalhao)
                    <x-cartao.campo rotulo="Talhão" icone="map">{{ $atividade->talhao->nome }}</x-cartao.campo>
                @else
                    <x-cartao.campo rotulo="Safra" icone="calendar">{{ $atividade->safra }}</x-cartao.campo>
                @endif
                <x-cartao.campo rotulo="Insumos" icone="beaker">{{ Number::currency($atividade->custo_insumos) }}</x-cartao.campo>
                <x-cartao.campo rotulo="Máquinas" icone="truck">{{ Number::currency($atividade->custo_maquinas) }}</x-cartao.campo>
                <x-cartao.campo rotulo="Mão de obra" icone="users">{{ Number::currency($atividade->custo_mao_obra) }}</x-cartao.campo>
                @if ($mostrarTalhao)
                    <x-cartao.campo rotulo="Safra" icone="calendar">{{ $atividade->safra }}</x-cartao.campo>
                @endif

                <x-slot:acoes>
                    <flux:button size="sm" variant="ghost" icon="eye" :href="route('atividades.show', $atividade)" wire:navigate>Ver detalhes</flux:button>
                </x-slot:acoes>
            </x-cartao>
        @endforeach
    </x-slot:cartoes>
</x-lista-responsiva>
