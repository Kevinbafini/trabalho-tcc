<x-layouts::app title="Máquinas">
    <x-cabecalho-pagina titulo="Máquinas" icone="truck" descricao="O custo por hora de cada máquina compõe o custo de maquinário das atividades (já incluindo os implementos que ela traciona).">
        <x-slot:acoes>
            <flux:button :href="route('maquinas.create', array_filter(['propriedade_id' => $filtroPropriedade]))" variant="primary" icon="plus" wire:navigate>Nova máquina</flux:button>
        </x-slot:acoes>
    </x-cabecalho-pagina>

    <x-busca>
        <x-slot:filtros>
            <x-busca.campo placeholder="Buscar por nome, tipo ou modelo..." :valor="$busca" />

            @if ($propriedades->count() > 1)
                <x-busca.filtro nome="propriedade_id" rotulo="Propriedade">
                    <flux:select.option value="">Todas as propriedades</flux:select.option>
                    @foreach ($propriedades as $propriedade)
                        <flux:select.option :value="$propriedade->id" :selected="$filtroPropriedade === $propriedade->id">{{ $propriedade->nome }}</flux:select.option>
                    @endforeach
                </x-busca.filtro>
            @endif
        </x-slot:filtros>

        @fragment('resultados')
            @if ($maquinas->isEmpty() && ($busca !== '' || $filtroPropriedade))
                <x-busca.sem-resultados :busca="$busca" />
            @elseif ($maquinas->isEmpty())
                <x-estado-vazio icone="truck" titulo="Nenhuma máquina cadastrada" descricao="Cadastre tratores, colhedoras e pulverizadores com o custo por hora de uso.">
                    <flux:button :href="route('maquinas.create')" variant="primary" icon="plus" wire:navigate>Cadastrar máquina</flux:button>
                </x-estado-vazio>
            @else
                <x-resumo-da-lista :total="$maquinas->total()" singular="máquina" plural="máquinas" />

                <x-lista-responsiva :paginacao="$maquinas" :alternador="false">
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
                                        <flux:table.cell variant="strong">
                                            <div class="flex items-center gap-3">
                                                <span class="flex size-8 items-center justify-center rounded-md bg-zinc-100 text-zinc-500 dark:bg-white/10 dark:text-zinc-300"><flux:icon.truck variant="micro" /></span>
                                                {{ $maquina->nome }}
                                            </div>
                                        </flux:table.cell>
                                        @if ($propriedades->count() > 1)
                                            <flux:table.cell>{{ $maquina->propriedade->nome }}</flux:table.cell>
                                        @endif
                                        <flux:table.cell>{{ $maquina->tipo }}</flux:table.cell>
                                        <flux:table.cell>{{ $maquina->modelo ?? '—' }}</flux:table.cell>
                                        <flux:table.cell align="end" class="tabular-nums">{{ $maquina->ano ?? '—' }}</flux:table.cell>
                                        <flux:table.cell align="end" class="tabular-nums">{{ Number::currency($maquina->custo_hora) }}/h</flux:table.cell>
                                        <flux:table.cell><x-selo-situacao :status="$maquina->status" /></flux:table.cell>
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
                            <x-cartao icone="truck" :titulo="$maquina->nome" :subtitulo="$maquina->tipo">
                                <x-slot:selo>
                                    <x-selo-situacao :status="$maquina->status" />
                                </x-slot:selo>

                                <x-cartao.campo rotulo="Custo por hora" icone="banknotes">{{ Number::currency($maquina->custo_hora) }}/h</x-cartao.campo>
                                <x-cartao.campo rotulo="Modelo / ano" icone="tag">{{ $maquina->modelo ?? '—' }} · {{ $maquina->ano ?? '—' }}</x-cartao.campo>
                                @if ($propriedades->count() > 1)
                                    <x-cartao.campo rotulo="Propriedade" icone="home-modern">{{ $maquina->propriedade->nome }}</x-cartao.campo>
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
        @endfragment
    </x-busca>
</x-layouts::app>
