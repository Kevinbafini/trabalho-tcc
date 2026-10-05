<x-layouts::app title="Propriedades">
    <x-cabecalho-pagina titulo="Propriedades" icone="home-modern" descricao="Propriedades rurais cadastradas. Cada propriedade é dividida em talhões e possui seus próprios recursos.">
        <x-slot:acoes>
            <flux:button :href="route('propriedades.create')" variant="primary" icon="plus" wire:navigate>Nova propriedade</flux:button>
        </x-slot:acoes>
    </x-cabecalho-pagina>

    <x-busca>
        <x-slot:filtros>
            <x-busca.campo placeholder="Buscar por nome, proprietário ou município..." :valor="$busca" />
        </x-slot:filtros>

        @fragment('resultados')
            @if ($propriedades->isEmpty() && $busca !== '')
                <x-busca.sem-resultados :busca="$busca" />
            @elseif ($propriedades->isEmpty())
                <x-estado-vazio icone="home-modern" titulo="Nenhuma propriedade cadastrada" descricao="Comece cadastrando a propriedade rural. Depois você poderá dividi-la em talhões e registrar as atividades.">
                    <flux:button :href="route('propriedades.create')" variant="primary" icon="plus" wire:navigate>Cadastrar propriedade</flux:button>
                </x-estado-vazio>
            @else
                <x-resumo-da-lista :total="$propriedades->total()" singular="propriedade" plural="propriedades" />

                <x-lista-responsiva :paginacao="$propriedades" :alternador="false">
                    <x-slot:tabela>
                        <flux:table>
                            <flux:table.columns>
                                <flux:table.column>Propriedade</flux:table.column>
                                <flux:table.column>Proprietário</flux:table.column>
                                <flux:table.column>Município</flux:table.column>
                                <flux:table.column align="end">Área total</flux:table.column>
                                <flux:table.column align="end">Talhões</flux:table.column>
                                <flux:table.column align="end"><span class="sr-only">Ações</span></flux:table.column>
                            </flux:table.columns>

                            <flux:table.rows>
                                @foreach ($propriedades as $propriedade)
                                    <flux:table.row :key="$propriedade->id">
                                        <flux:table.cell variant="strong">
                                            <div class="flex items-center gap-3">
                                                <span class="flex size-8 items-center justify-center rounded-md bg-zinc-100 text-zinc-500 dark:bg-white/10 dark:text-zinc-300"><flux:icon.home-modern variant="micro" /></span>
                                                <flux:link :href="route('propriedades.show', $propriedade)" wire:navigate>{{ $propriedade->nome }}</flux:link>
                                            </div>
                                        </flux:table.cell>
                                        <flux:table.cell>{{ $propriedade->proprietario }}</flux:table.cell>
                                        <flux:table.cell>
                                            <span class="inline-flex items-center gap-1"><flux:icon.map-pin variant="micro" class="text-zinc-400" />{{ $propriedade->municipio }}/{{ $propriedade->uf }}</span>
                                        </flux:table.cell>
                                        <flux:table.cell align="end" class="tabular-nums">{{ Number::format($propriedade->area_total, 2) }} ha</flux:table.cell>
                                        <flux:table.cell align="end" class="tabular-nums">{{ $propriedade->talhoes_count }}</flux:table.cell>
                                        <flux:table.cell align="end">
                                            <div class="flex justify-end gap-1">
                                                <flux:button size="sm" variant="ghost" icon="eye" :href="route('propriedades.show', $propriedade)" wire:navigate>Ver</flux:button>
                                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('propriedades.edit', $propriedade)" wire:navigate>Editar</flux:button>
                                                <x-confirmar-exclusao
                                                    :acao="route('propriedades.destroy', $propriedade)"
                                                    :nome="'propriedade-'.$propriedade->id"
                                                    :mensagem="'Deseja excluir a propriedade “'.$propriedade->nome.'”? Só é possível excluir propriedades sem talhões, recursos ou atividades.'"
                                                />
                                            </div>
                                        </flux:table.cell>
                                    </flux:table.row>
                                @endforeach
                            </flux:table.rows>
                        </flux:table>
                    </x-slot:tabela>

                    <x-slot:cartoes>
                        @foreach ($propriedades as $propriedade)
                            <x-cartao icone="home-modern" :titulo="$propriedade->nome" :link="route('propriedades.show', $propriedade)" :subtitulo="$propriedade->municipio.'/'.$propriedade->uf">
                                <x-cartao.campo rotulo="Proprietário" icone="user">{{ $propriedade->proprietario }}</x-cartao.campo>
                                <x-cartao.campo rotulo="Área total" icone="arrows-pointing-out">{{ Number::format($propriedade->area_total, 2) }} ha</x-cartao.campo>
                                <x-cartao.campo rotulo="Talhões" icone="map">{{ $propriedade->talhoes_count }}</x-cartao.campo>

                                <x-slot:acoes>
                                    <flux:button size="sm" variant="ghost" icon="eye" :href="route('propriedades.show', $propriedade)" wire:navigate>Ver</flux:button>
                                    <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('propriedades.edit', $propriedade)" wire:navigate>Editar</flux:button>
                                    <x-confirmar-exclusao
                                        :acao="route('propriedades.destroy', $propriedade)"
                                        :nome="'propriedade-cartao-'.$propriedade->id"
                                        :mensagem="'Deseja excluir a propriedade “'.$propriedade->nome.'”? Só é possível excluir propriedades sem talhões, recursos ou atividades.'"
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
