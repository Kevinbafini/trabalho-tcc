<x-layouts::app title="Insumos">
    <x-cabecalho-pagina titulo="Insumos" descricao="O valor unitário de cada insumo compõe o custo de insumos das atividades. O estoque é apenas informativo.">
        <x-slot:acoes>
            <flux:button :href="route('insumos.create', array_filter(['propriedade_id' => $filtroPropriedade]))" variant="primary" icon="plus" wire:navigate>Novo insumo</flux:button>
        </x-slot:acoes>
    </x-cabecalho-pagina>

    <x-filtro-propriedade :propriedades="$propriedades" :selecionada="$filtroPropriedade" />

    @if ($insumos->isEmpty())
        <x-estado-vazio icone="beaker" titulo="Nenhum insumo cadastrado" descricao="Cadastre sementes, fertilizantes e defensivos com o valor unitário.">
            <flux:button :href="route('insumos.create')" variant="primary" icon="plus" wire:navigate>Cadastrar insumo</flux:button>
        </x-estado-vazio>
    @else
        <x-lista-responsiva :paginacao="$insumos">
            <x-slot:tabela>
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Insumo</flux:table.column>
                        @if ($propriedades->count() > 1)
                            <flux:table.column>Propriedade</flux:table.column>
                        @endif
                        <flux:table.column>Categoria</flux:table.column>
                        <flux:table.column align="end">Valor unitário</flux:table.column>
                        <flux:table.column align="end">Estoque</flux:table.column>
                        <flux:table.column>Situação</flux:table.column>
                        <flux:table.column align="end"><span class="sr-only">Ações</span></flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($insumos as $insumo)
                            <flux:table.row :key="$insumo->id">
                                <flux:table.cell variant="strong">{{ $insumo->nome }}</flux:table.cell>
                                @if ($propriedades->count() > 1)
                                    <flux:table.cell>{{ $insumo->propriedade->nome }}</flux:table.cell>
                                @endif
                                <flux:table.cell>{{ $insumo->categoria->rotulo() }}</flux:table.cell>
                                <flux:table.cell align="end" class="tabular-nums">{{ Number::currency($insumo->valor_unitario) }}/{{ $insumo->unidade->value }}</flux:table.cell>
                                <flux:table.cell align="end" class="tabular-nums">{{ Number::format($insumo->quantidade_estoque, maxPrecision: 3) }} {{ $insumo->unidade->value }}</flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge size="sm" :color="$insumo->status->cor()">{{ $insumo->status->rotulo() }}</flux:badge>
                                </flux:table.cell>
                                <flux:table.cell align="end">
                                    <div class="flex justify-end gap-1">
                                        <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('insumos.edit', $insumo)" wire:navigate>Editar</flux:button>
                                        <x-confirmar-exclusao
                                            :acao="route('insumos.destroy', $insumo)"
                                            :nome="'insumo-'.$insumo->id"
                                            :mensagem="'Deseja excluir o insumo “'.$insumo->nome.'”? Insumos já utilizados em atividades não podem ser excluídos.'"
                                        />
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </x-slot:tabela>

            <x-slot:cartoes>
                @foreach ($insumos as $insumo)
                    <x-cartao :titulo="$insumo->nome" :subtitulo="$insumo->categoria->rotulo()">
                        <x-slot:selo>
                            <flux:badge size="sm" :color="$insumo->status->cor()">{{ $insumo->status->rotulo() }}</flux:badge>
                        </x-slot:selo>

                        <x-cartao.campo rotulo="Valor unitário">{{ Number::currency($insumo->valor_unitario) }}/{{ $insumo->unidade->value }}</x-cartao.campo>
                        <x-cartao.campo rotulo="Estoque">{{ Number::format($insumo->quantidade_estoque, maxPrecision: 3) }} {{ $insumo->unidade->value }}</x-cartao.campo>
                        @if ($propriedades->count() > 1)
                            <x-cartao.campo rotulo="Propriedade">{{ $insumo->propriedade->nome }}</x-cartao.campo>
                        @endif

                        <x-slot:acoes>
                            <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('insumos.edit', $insumo)" wire:navigate>Editar</flux:button>
                            <x-confirmar-exclusao
                                :acao="route('insumos.destroy', $insumo)"
                                :nome="'insumo-cartao-'.$insumo->id"
                                :mensagem="'Deseja excluir o insumo “'.$insumo->nome.'”? Insumos já utilizados em atividades não podem ser excluídos.'"
                            />
                        </x-slot:acoes>
                    </x-cartao>
                @endforeach
            </x-slot:cartoes>
        </x-lista-responsiva>
    @endif
</x-layouts::app>
