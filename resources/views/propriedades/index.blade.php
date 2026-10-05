<x-layouts::app title="Propriedades">
    <x-cabecalho-pagina titulo="Propriedades" descricao="Propriedades rurais cadastradas. Cada propriedade é dividida em talhões e possui seus próprios recursos.">
        <x-slot:acoes>
            <flux:button :href="route('propriedades.create')" variant="primary" icon="plus" wire:navigate>Nova propriedade</flux:button>
        </x-slot:acoes>
    </x-cabecalho-pagina>

    @if ($propriedades->isEmpty())
        <x-estado-vazio icone="home-modern" titulo="Nenhuma propriedade cadastrada" descricao="Comece cadastrando a propriedade rural. Depois você poderá dividi-la em talhões e registrar as atividades.">
            <flux:button :href="route('propriedades.create')" variant="primary" icon="plus" wire:navigate>Cadastrar propriedade</flux:button>
        </x-estado-vazio>
    @else
        <flux:table :paginate="$propriedades">
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
                            <flux:link :href="route('propriedades.show', $propriedade)" wire:navigate>{{ $propriedade->nome }}</flux:link>
                        </flux:table.cell>
                        <flux:table.cell>{{ $propriedade->proprietario }}</flux:table.cell>
                        <flux:table.cell>{{ $propriedade->municipio }}/{{ $propriedade->uf }}</flux:table.cell>
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
    @endif
</x-layouts::app>
