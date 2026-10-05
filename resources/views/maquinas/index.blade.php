<x-layouts::app title="Máquinas">
    <x-cabecalho-pagina titulo="Máquinas" descricao="O custo por hora de cada máquina compõe o custo de maquinário das atividades (já incluindo os implementos que ela traciona).">
        <x-slot:acoes>
            <flux:button :href="route('maquinas.create', array_filter(['propriedade_id' => $filtroPropriedade]))" variant="primary" icon="plus" wire:navigate>Nova máquina</flux:button>
        </x-slot:acoes>
    </x-cabecalho-pagina>

    <x-filtro-propriedade :propriedades="$propriedades" :selecionada="$filtroPropriedade" />

    @if ($maquinas->isEmpty())
        <x-estado-vazio icone="truck" titulo="Nenhuma máquina cadastrada" descricao="Cadastre tratores, colhedoras e pulverizadores com o custo por hora de uso.">
            <flux:button :href="route('maquinas.create')" variant="primary" icon="plus" wire:navigate>Cadastrar máquina</flux:button>
        </x-estado-vazio>
    @else
        <x-lista-responsiva :paginacao="$maquinas">
            <x-slot:tabela>
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Máquina</flux:table.column>
                        @if ($propriedades->count() > 1)
                            <flux:table.column>Propriedade</flux:table.column>
                        @endif
                        <flux:table.column>Tipo</flux:table.column>
                        <flux:table.column>Modelo</flux:table.column>
                        <flux:table.column align="end">Ano</flux:table.column>
                        <flux:table.column align="end">Custo por hora</flux:table.column>
                        <flux:table.column>Situação</flux:table.column>
                        <flux:table.column align="end"><span class="sr-only">Ações</span></flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($maquinas as $maquina)
                            <flux:table.row :key="$maquina->id">
                                <flux:table.cell variant="strong">{{ $maquina->nome }}</flux:table.cell>
                                @if ($propriedades->count() > 1)
                                    <flux:table.cell>{{ $maquina->propriedade->nome }}</flux:table.cell>
                                @endif
                                <flux:table.cell>{{ $maquina->tipo }}</flux:table.cell>
                                <flux:table.cell>{{ $maquina->modelo ?? '—' }}</flux:table.cell>
                                <flux:table.cell align="end" class="tabular-nums">{{ $maquina->ano ?? '—' }}</flux:table.cell>
                                <flux:table.cell align="end" class="tabular-nums">{{ Number::currency($maquina->custo_hora) }}/h</flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge size="sm" :color="$maquina->status->cor()">{{ $maquina->status->rotulo() }}</flux:badge>
                                </flux:table.cell>
                                <flux:table.cell align="end">
                                    <div class="flex justify-end gap-1">
                                        <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('maquinas.edit', $maquina)" wire:navigate>Editar</flux:button>
                                        <x-confirmar-exclusao
                                            :acao="route('maquinas.destroy', $maquina)"
                                            :nome="'maquina-'.$maquina->id"
                                            :mensagem="'Deseja excluir a máquina “'.$maquina->nome.'”? Máquinas já utilizadas em atividades não podem ser excluídas.'"
                                        />
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </x-slot:tabela>

            <x-slot:cartoes>
                @foreach ($maquinas as $maquina)
                    <x-cartao :titulo="$maquina->nome" :subtitulo="$maquina->tipo">
                        <x-slot:selo>
                            <flux:badge size="sm" :color="$maquina->status->cor()">{{ $maquina->status->rotulo() }}</flux:badge>
                        </x-slot:selo>

                        <x-cartao.campo rotulo="Custo por hora">{{ Number::currency($maquina->custo_hora) }}/h</x-cartao.campo>
                        <x-cartao.campo rotulo="Modelo / ano">{{ $maquina->modelo ?? '—' }} · {{ $maquina->ano ?? '—' }}</x-cartao.campo>
                        @if ($propriedades->count() > 1)
                            <x-cartao.campo rotulo="Propriedade">{{ $maquina->propriedade->nome }}</x-cartao.campo>
                        @endif

                        <x-slot:acoes>
                            <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('maquinas.edit', $maquina)" wire:navigate>Editar</flux:button>
                            <x-confirmar-exclusao
                                :acao="route('maquinas.destroy', $maquina)"
                                :nome="'maquina-cartao-'.$maquina->id"
                                :mensagem="'Deseja excluir a máquina “'.$maquina->nome.'”? Máquinas já utilizadas em atividades não podem ser excluídas.'"
                            />
                        </x-slot:acoes>
                    </x-cartao>
                @endforeach
            </x-slot:cartoes>
        </x-lista-responsiva>
    @endif
</x-layouts::app>
