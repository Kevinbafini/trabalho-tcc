<x-layouts::app title="Implementos">
    <x-cabecalho-pagina titulo="Implementos" icone="wrench-screwdriver" descricao="Implementos são associados às atividades, mas não têm custo próprio: o custo operacional está incluído no custo por hora da máquina.">
        <x-slot:acoes>
            <flux:button :href="route('implementos.create', array_filter(['propriedade_id' => $filtroPropriedade]))" variant="primary" icon="plus" wire:navigate>Novo implemento</flux:button>
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
            @if ($implementos->isEmpty() && ($busca !== '' || $filtroPropriedade))
                <x-busca.sem-resultados :busca="$busca" />
            @elseif ($implementos->isEmpty())
                <x-estado-vazio icone="wrench-screwdriver" titulo="Nenhum implemento cadastrado" descricao="Cadastre grades, plantadeiras, pulverizadores de barras e outros implementos.">
                    <flux:button :href="route('implementos.create')" variant="primary" icon="plus" wire:navigate>Cadastrar implemento</flux:button>
                </x-estado-vazio>
            @else
                <x-resumo-da-lista :total="$implementos->total()" singular="implemento" plural="implementos" />

                <x-lista-responsiva :paginacao="$implementos" :alternador="false">
                    <x-slot:tabela>
                        <flux:table>
                            <flux:table.columns>
                                <flux:table.column>Implemento</flux:table.column>
                                @if ($propriedades->count() > 1)
                                    <flux:table.column>Propriedade</flux:table.column>
                                @endif
                                <flux:table.column>Tipo</flux:table.column>
                                <flux:table.column>Modelo</flux:table.column>
                                <flux:table.column>Situação</flux:table.column>
                                <flux:table.column align="end"><span class="sr-only">Ações</span></flux:table.column>
                            </flux:table.columns>

                            <flux:table.rows>
                                @foreach ($implementos as $implemento)
                                    <flux:table.row :key="$implemento->id">
                                        <flux:table.cell variant="strong">
                                            <div class="flex items-center gap-3">
                                                <span class="flex size-8 items-center justify-center rounded-md bg-zinc-100 text-zinc-500 dark:bg-white/10 dark:text-zinc-300"><flux:icon.wrench-screwdriver variant="micro" /></span>
                                                {{ $implemento->nome }}
                                            </div>
                                        </flux:table.cell>
                                        @if ($propriedades->count() > 1)
                                            <flux:table.cell>{{ $implemento->propriedade->nome }}</flux:table.cell>
                                        @endif
                                        <flux:table.cell>{{ $implemento->tipo }}</flux:table.cell>
                                        <flux:table.cell>{{ $implemento->modelo ?? '—' }}</flux:table.cell>
                                        <flux:table.cell><x-selo-situacao :status="$implemento->status" /></flux:table.cell>
                                        <flux:table.cell align="end">
                                            <div class="flex justify-end gap-1">
                                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('implementos.edit', $implemento)" wire:navigate>Editar</flux:button>
                                                <x-confirmar-exclusao
                                                    :acao="route('implementos.destroy', $implemento)"
                                                    :nome="'implemento-'.$implemento->id"
                                                    :mensagem="'Deseja excluir o implemento “'.$implemento->nome.'”? Implementos já utilizados em atividades não podem ser excluídos.'"
                                                />
                                            </div>
                                        </flux:table.cell>
                                    </flux:table.row>
                                @endforeach
                            </flux:table.rows>
                        </flux:table>
                    </x-slot:tabela>

                    <x-slot:cartoes>
                        @foreach ($implementos as $implemento)
                            <x-cartao icone="wrench-screwdriver" :titulo="$implemento->nome" :subtitulo="$implemento->tipo">
                                <x-slot:selo>
                                    <x-selo-situacao :status="$implemento->status" />
                                </x-slot:selo>

                                <x-cartao.campo rotulo="Modelo" icone="tag">{{ $implemento->modelo ?? '—' }}</x-cartao.campo>
                                @if ($propriedades->count() > 1)
                                    <x-cartao.campo rotulo="Propriedade" icone="home-modern">{{ $implemento->propriedade->nome }}</x-cartao.campo>
                                @endif

                                <x-slot:acoes>
                                    <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('implementos.edit', $implemento)" wire:navigate>Editar</flux:button>
                                    <x-confirmar-exclusao
                                        :acao="route('implementos.destroy', $implemento)"
                                        :nome="'implemento-cartao-'.$implemento->id"
                                        :mensagem="'Deseja excluir o implemento “'.$implemento->nome.'”? Implementos já utilizados em atividades não podem ser excluídos.'"
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
