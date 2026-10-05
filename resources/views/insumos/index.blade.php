<x-layouts::app title="Insumos">
    <x-cabecalho-pagina titulo="Insumos" icone="beaker" descricao="O valor unitário de cada insumo compõe o custo de insumos das atividades. O estoque é apenas informativo.">
        <x-slot:acoes>
            <flux:button :href="route('insumos.create', array_filter(['propriedade_id' => $filtroPropriedade]))" variant="primary" icon="plus" wire:navigate>Novo insumo</flux:button>
        </x-slot:acoes>
    </x-cabecalho-pagina>

    <x-busca>
        <x-slot:filtros>
            <x-busca.campo placeholder="Buscar insumo pelo nome..." :valor="$busca" />

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
            @if ($insumos->isEmpty() && ($busca !== '' || $filtroPropriedade))
                <x-busca.sem-resultados :busca="$busca" />
            @elseif ($insumos->isEmpty())
                <x-estado-vazio icone="beaker" titulo="Nenhum insumo cadastrado" descricao="Cadastre sementes, fertilizantes e defensivos com o valor unitário.">
                    <flux:button :href="route('insumos.create')" variant="primary" icon="plus" wire:navigate>Cadastrar insumo</flux:button>
                </x-estado-vazio>
            @else
                <x-resumo-da-lista :total="$insumos->total()" singular="insumo" plural="insumos" />

                <x-lista-responsiva :paginacao="$insumos" :alternador="false">
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
                                        <flux:table.cell variant="strong">
                                            <div class="flex items-center gap-3">
                                                <span class="flex size-8 items-center justify-center rounded-md bg-zinc-100 text-zinc-500 dark:bg-white/10 dark:text-zinc-300"><flux:icon.beaker variant="micro" /></span>
                                                {{ $insumo->nome }}
                                            </div>
                                        </flux:table.cell>
                                        @if ($propriedades->count() > 1)
                                            <flux:table.cell>{{ $insumo->propriedade->nome }}</flux:table.cell>
                                        @endif
                                        <flux:table.cell><flux:badge size="sm">{{ $insumo->categoria->rotulo() }}</flux:badge></flux:table.cell>
                                        <flux:table.cell align="end" class="tabular-nums">{{ Number::currency($insumo->valor_unitario) }}/{{ $insumo->unidade->value }}</flux:table.cell>
                                        <flux:table.cell align="end" class="tabular-nums">{{ Number::format($insumo->quantidade_estoque, maxPrecision: 3) }} {{ $insumo->unidade->value }}</flux:table.cell>
                                        <flux:table.cell><x-selo-situacao :status="$insumo->status" /></flux:table.cell>
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
                            <x-cartao icone="beaker" :titulo="$insumo->nome" :subtitulo="$insumo->categoria->rotulo()">
                                <x-slot:selo>
                                    <x-selo-situacao :status="$insumo->status" />
                                </x-slot:selo>

                                <x-cartao.campo rotulo="Valor unitário" icone="banknotes">{{ Number::currency($insumo->valor_unitario) }}/{{ $insumo->unidade->value }}</x-cartao.campo>
                                <x-cartao.campo rotulo="Estoque" icone="archive-box">{{ Number::format($insumo->quantidade_estoque, maxPrecision: 3) }} {{ $insumo->unidade->value }}</x-cartao.campo>
                                @if ($propriedades->count() > 1)
                                    <x-cartao.campo rotulo="Propriedade" icone="home-modern">{{ $insumo->propriedade->nome }}</x-cartao.campo>
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
        @endfragment
    </x-busca>
</x-layouts::app>
