<x-layouts::app title="Talhões">
    <x-cabecalho-pagina titulo="Talhões" descricao="Divisões da propriedade onde as atividades são realizadas. O custo por hectare é calculado sobre a área de cada talhão.">
        <x-slot:acoes>
            <flux:button :href="route('talhoes.create', array_filter(['propriedade_id' => $filtroPropriedade]))" variant="primary" icon="plus" wire:navigate>Novo talhão</flux:button>
        </x-slot:acoes>
    </x-cabecalho-pagina>

    <x-filtro-propriedade :propriedades="$propriedades" :selecionada="$filtroPropriedade" />

    @if ($talhoes->isEmpty())
        <x-estado-vazio icone="map" titulo="Nenhum talhão cadastrado" descricao="Cadastre os talhões da propriedade, informando área, cultura e safra.">
            <flux:button :href="route('talhoes.create')" variant="primary" icon="plus" wire:navigate>Cadastrar talhão</flux:button>
        </x-estado-vazio>
    @else
        <x-lista-responsiva :paginacao="$talhoes">
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
                                    <flux:link :href="route('talhoes.show', $talhao)" wire:navigate>{{ $talhao->nome }}</flux:link>
                                </flux:table.cell>
                                @if ($propriedades->count() > 1)
                                    <flux:table.cell>{{ $talhao->propriedade->nome }}</flux:table.cell>
                                @endif
                                <flux:table.cell>{{ $talhao->cultura }}</flux:table.cell>
                                <flux:table.cell>{{ $talhao->safra }}</flux:table.cell>
                                <flux:table.cell align="end" class="tabular-nums">{{ Number::format($talhao->area, 2) }} ha</flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge size="sm" :color="$talhao->status->cor()">{{ $talhao->status->rotulo() }}</flux:badge>
                                </flux:table.cell>
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
                    <x-cartao :titulo="$talhao->nome" :link="route('talhoes.show', $talhao)" :subtitulo="$propriedades->count() > 1 ? $talhao->propriedade->nome : null">
                        <x-slot:selo>
                            <flux:badge size="sm" :color="$talhao->status->cor()">{{ $talhao->status->rotulo() }}</flux:badge>
                        </x-slot:selo>

                        <x-cartao.campo rotulo="Cultura">{{ $talhao->cultura }}</x-cartao.campo>
                        <x-cartao.campo rotulo="Safra">{{ $talhao->safra }}</x-cartao.campo>
                        <x-cartao.campo rotulo="Área">{{ Number::format($talhao->area, 2) }} ha</x-cartao.campo>

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
</x-layouts::app>
