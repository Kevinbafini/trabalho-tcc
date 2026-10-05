<x-layouts::app title="Talhões">
    <x-cabecalho-pagina titulo="Talhões" icone="map" descricao="Divisões da propriedade onde as atividades são realizadas. O custo por hectare é calculado sobre a área de cada talhão.">
        <x-slot:acoes>
            <flux:button :href="route('talhoes.create', array_filter(['propriedade_id' => $filtroPropriedade]))" variant="primary" icon="plus" wire:navigate>Novo talhão</flux:button>
        </x-slot:acoes>
    </x-cabecalho-pagina>

    <x-busca>
        <x-slot:filtros>
            <x-busca.campo placeholder="Buscar por nome, cultura ou safra..." :valor="$busca" />

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
            @if ($talhoes->isEmpty() && ($busca !== '' || $filtroPropriedade))
                <x-busca.sem-resultados :busca="$busca" />
            @elseif ($talhoes->isEmpty())
                <x-estado-vazio icone="map" titulo="Nenhum talhão cadastrado" descricao="Cadastre os talhões da propriedade, informando área, cultura e safra.">
                    <flux:button :href="route('talhoes.create')" variant="primary" icon="plus" wire:navigate>Cadastrar talhão</flux:button>
                </x-estado-vazio>
            @else
                <x-resumo-da-lista :total="$talhoes->total()" singular="talhão" plural="talhões">
                    {{ Number::format($areaEncontrada, 2) }} ha
                </x-resumo-da-lista>

                <x-lista-responsiva :paginacao="$talhoes" :alternador="false">
                    <x-slot:tabela>
                        <flux:table>
                            <flux:table.columns>
                                <flux:table.column>Talhão</flux:table.column>
                                @if ($propriedades->count() > 1)
                                    <flux:table.column>Propriedade</flux:table.column>
                                @endif
                                <flux:table.column>Cultura</flux:table.column>
                                <flux:table.column>Safra</flux:table.column>
                                <flux:table.column align="end">Área</flux:table.column>
                                <flux:table.column>Situação</flux:table.column>
                                <flux:table.column align="end"><span class="sr-only">Ações</span></flux:table.column>
                            </flux:table.columns>

                            <flux:table.rows>
                                @foreach ($talhoes as $talhao)
                                    <flux:table.row :key="$talhao->id">
                                        <flux:table.cell variant="strong">
                                            <div class="flex items-center gap-3">
                                                <span class="flex size-8 items-center justify-center rounded-md bg-zinc-100 text-zinc-500 dark:bg-white/10 dark:text-zinc-300"><flux:icon.map variant="micro" /></span>
                                                <flux:link :href="route('talhoes.show', $talhao)" wire:navigate>{{ $talhao->nome }}</flux:link>
                                            </div>
                                        </flux:table.cell>
                                        @if ($propriedades->count() > 1)
                                            <flux:table.cell>{{ $talhao->propriedade->nome }}</flux:table.cell>
                                        @endif
                                        <flux:table.cell>{{ $talhao->cultura }}</flux:table.cell>
                                        <flux:table.cell>{{ $talhao->safra }}</flux:table.cell>
                                        <flux:table.cell align="end" class="tabular-nums">{{ Number::format($talhao->area, 2) }} ha</flux:table.cell>
                                        <flux:table.cell><x-selo-situacao :status="$talhao->status" /></flux:table.cell>
                                        <flux:table.cell align="end">
                                            <div class="flex justify-end gap-1">
                                                <flux:button size="sm" variant="ghost" icon="eye" :href="route('talhoes.show', $talhao)" wire:navigate>Ver</flux:button>
                                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('talhoes.edit', $talhao)" wire:navigate>Editar</flux:button>
                                                <x-confirmar-exclusao
                                                    :acao="route('talhoes.destroy', $talhao)"
                                                    :nome="'talhao-'.$talhao->id"
                                                    :mensagem="'Deseja excluir o talhão “'.$talhao->nome.'”? Talhões com atividades registradas não podem ser excluídos.'"
                                                />
                                            </div>
                                        </flux:table.cell>
                                    </flux:table.row>
                                @endforeach
                            </flux:table.rows>
                        </flux:table>
                    </x-slot:tabela>

                    <x-slot:cartoes>
                        @foreach ($talhoes as $talhao)
                            <x-cartao icone="map" :titulo="$talhao->nome" :link="route('talhoes.show', $talhao)" :subtitulo="$propriedades->count() > 1 ? $talhao->propriedade->nome : $talhao->cultura.' · Safra '.$talhao->safra">
                                <x-slot:selo>
                                    <x-selo-situacao :status="$talhao->status" />
                                </x-slot:selo>

                                <x-cartao.campo rotulo="Cultura" icone="sparkles">{{ $talhao->cultura }}</x-cartao.campo>
                                <x-cartao.campo rotulo="Safra" icone="calendar-days">{{ $talhao->safra }}</x-cartao.campo>
                                <x-cartao.campo rotulo="Área" icone="arrows-pointing-out">{{ Number::format($talhao->area, 2) }} ha</x-cartao.campo>

                                <x-slot:acoes>
                                    <flux:button size="sm" variant="ghost" icon="eye" :href="route('talhoes.show', $talhao)" wire:navigate>Ver</flux:button>
                                    <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('talhoes.edit', $talhao)" wire:navigate>Editar</flux:button>
                                    <x-confirmar-exclusao
                                        :acao="route('talhoes.destroy', $talhao)"
                                        :nome="'talhao-cartao-'.$talhao->id"
                                        :mensagem="'Deseja excluir o talhão “'.$talhao->nome.'”? Talhões com atividades registradas não podem ser excluídos.'"
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
