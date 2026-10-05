<x-layouts::app title="Funcionários">
    <x-cabecalho-pagina titulo="Funcionários" icone="users" descricao="O custo por hora de cada funcionário compõe o custo de mão de obra das atividades.">
        <x-slot:acoes>
            <flux:button :href="route('funcionarios.create', array_filter(['propriedade_id' => $filtroPropriedade]))" variant="primary" icon="plus" wire:navigate>Novo funcionário</flux:button>
        </x-slot:acoes>
    </x-cabecalho-pagina>

    <x-busca>
        <x-slot:filtros>
            <x-busca.campo placeholder="Buscar por nome ou função..." :valor="$busca" />

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
            @if ($funcionarios->isEmpty() && ($busca !== '' || $filtroPropriedade))
                <x-busca.sem-resultados :busca="$busca" />
            @elseif ($funcionarios->isEmpty())
                <x-estado-vazio icone="users" titulo="Nenhum funcionário cadastrado" descricao="Cadastre os funcionários com o custo por hora para calcular a mão de obra das atividades.">
                    <flux:button :href="route('funcionarios.create')" variant="primary" icon="plus" wire:navigate>Cadastrar funcionário</flux:button>
                </x-estado-vazio>
            @else
                <x-resumo-da-lista :total="$funcionarios->total()" singular="funcionário" plural="funcionários" />

                <x-lista-responsiva :paginacao="$funcionarios" :alternador="false">
                    <x-slot:tabela>
                        <flux:table>
                            <flux:table.columns>
                                <flux:table.column>Nome</flux:table.column>
                                @if ($propriedades->count() > 1)
                                    <flux:table.column>Propriedade</flux:table.column>
                                @endif
                                <flux:table.column>Função</flux:table.column>
                                <flux:table.column align="end">Custo por hora</flux:table.column>
                                <flux:table.column>Situação</flux:table.column>
                                <flux:table.column align="end"><span class="sr-only">Ações</span></flux:table.column>
                            </flux:table.columns>

                            <flux:table.rows>
                                @foreach ($funcionarios as $funcionario)
                                    <flux:table.row :key="$funcionario->id">
                                        <flux:table.cell variant="strong">
                                            <div class="flex items-center gap-3">
                                                <flux:avatar size="xs" :name="$funcionario->nome" />
                                                {{ $funcionario->nome }}
                                            </div>
                                        </flux:table.cell>
                                        @if ($propriedades->count() > 1)
                                            <flux:table.cell>{{ $funcionario->propriedade->nome }}</flux:table.cell>
                                        @endif
                                        <flux:table.cell>{{ $funcionario->funcao }}</flux:table.cell>
                                        <flux:table.cell align="end" class="tabular-nums">{{ Number::currency($funcionario->custo_hora) }}/h</flux:table.cell>
                                        <flux:table.cell><x-selo-situacao :status="$funcionario->status" /></flux:table.cell>
                                        <flux:table.cell align="end">
                                            <div class="flex justify-end gap-1">
                                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('funcionarios.edit', $funcionario)" wire:navigate>Editar</flux:button>
                                                <x-confirmar-exclusao
                                                    :acao="route('funcionarios.destroy', $funcionario)"
                                                    :nome="'funcionario-'.$funcionario->id"
                                                    :mensagem="'Deseja excluir o funcionário “'.$funcionario->nome.'”? Funcionários que já participaram de atividades não podem ser excluídos.'"
                                                />
                                            </div>
                                        </flux:table.cell>
                                    </flux:table.row>
                                @endforeach
                            </flux:table.rows>
                        </flux:table>
                    </x-slot:tabela>

                    <x-slot:cartoes>
                        @foreach ($funcionarios as $funcionario)
                            <x-cartao :titulo="$funcionario->nome" :subtitulo="$funcionario->funcao">
                                <x-slot:avatar>
                                    <flux:avatar :name="$funcionario->nome" />
                                </x-slot:avatar>
                                <x-slot:selo>
                                    <x-selo-situacao :status="$funcionario->status" />
                                </x-slot:selo>

                                <x-cartao.campo rotulo="Custo por hora" icone="banknotes">{{ Number::currency($funcionario->custo_hora) }}/h</x-cartao.campo>
                                @if ($propriedades->count() > 1)
                                    <x-cartao.campo rotulo="Propriedade" icone="home-modern">{{ $funcionario->propriedade->nome }}</x-cartao.campo>
                                @endif

                                <x-slot:acoes>
                                    <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('funcionarios.edit', $funcionario)" wire:navigate>Editar</flux:button>
                                    <x-confirmar-exclusao
                                        :acao="route('funcionarios.destroy', $funcionario)"
                                        :nome="'funcionario-cartao-'.$funcionario->id"
                                        :mensagem="'Deseja excluir o funcionário “'.$funcionario->nome.'”? Funcionários que já participaram de atividades não podem ser excluídos.'"
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
