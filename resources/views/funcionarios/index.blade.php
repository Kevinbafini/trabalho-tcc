<x-layouts::app title="Funcionários">
    <x-cabecalho-pagina titulo="Funcionários" descricao="O custo por hora de cada funcionário compõe o custo de mão de obra das atividades.">
        <x-slot:acoes>
            <flux:button :href="route('funcionarios.create', array_filter(['propriedade_id' => $filtroPropriedade]))" variant="primary" icon="plus" wire:navigate>Novo funcionário</flux:button>
        </x-slot:acoes>
    </x-cabecalho-pagina>

    <x-filtro-propriedade :propriedades="$propriedades" :selecionada="$filtroPropriedade" />

    @if ($funcionarios->isEmpty())
        <x-estado-vazio icone="users" titulo="Nenhum funcionário cadastrado" descricao="Cadastre os funcionários com o custo por hora para calcular a mão de obra das atividades.">
            <flux:button :href="route('funcionarios.create')" variant="primary" icon="plus" wire:navigate>Cadastrar funcionário</flux:button>
        </x-estado-vazio>
    @else
        <x-lista-responsiva :paginacao="$funcionarios">
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
                                <flux:table.cell variant="strong">{{ $funcionario->nome }}</flux:table.cell>
                                @if ($propriedades->count() > 1)
                                    <flux:table.cell>{{ $funcionario->propriedade->nome }}</flux:table.cell>
                                @endif
                                <flux:table.cell>{{ $funcionario->funcao }}</flux:table.cell>
                                <flux:table.cell align="end" class="tabular-nums">{{ Number::currency($funcionario->custo_hora) }}/h</flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge size="sm" :color="$funcionario->status->cor()">{{ $funcionario->status->rotulo() }}</flux:badge>
                                </flux:table.cell>
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
                        <x-slot:selo>
                            <flux:badge size="sm" :color="$funcionario->status->cor()">{{ $funcionario->status->rotulo() }}</flux:badge>
                        </x-slot:selo>

                        <x-cartao.campo rotulo="Custo por hora">{{ Number::currency($funcionario->custo_hora) }}/h</x-cartao.campo>
                        @if ($propriedades->count() > 1)
                            <x-cartao.campo rotulo="Propriedade">{{ $funcionario->propriedade->nome }}</x-cartao.campo>
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
</x-layouts::app>
