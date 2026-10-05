<x-layouts::app title="Implementos">
    <x-cabecalho-pagina titulo="Implementos" descricao="Implementos são associados às atividades, mas não têm custo próprio: o custo operacional está incluído no custo por hora da máquina.">
        <x-slot:acoes>
            <flux:button :href="route('implementos.create', array_filter(['propriedade_id' => $filtroPropriedade]))" variant="primary" icon="plus" wire:navigate>Novo implemento</flux:button>
        </x-slot:acoes>
    </x-cabecalho-pagina>

    <x-filtro-propriedade :propriedades="$propriedades" :selecionada="$filtroPropriedade" />

    @if ($implementos->isEmpty())
        <x-estado-vazio icone="wrench-screwdriver" titulo="Nenhum implemento cadastrado" descricao="Cadastre grades, plantadeiras, pulverizadores de barras e outros implementos.">
            <flux:button :href="route('implementos.create')" variant="primary" icon="plus" wire:navigate>Cadastrar implemento</flux:button>
        </x-estado-vazio>
    @else
        <flux:table :paginate="$implementos">
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
                        <flux:table.cell variant="strong">{{ $implemento->nome }}</flux:table.cell>
                        @if ($propriedades->count() > 1)
                            <flux:table.cell>{{ $implemento->propriedade->nome }}</flux:table.cell>
                        @endif
                        <flux:table.cell>{{ $implemento->tipo }}</flux:table.cell>
                        <flux:table.cell>{{ $implemento->modelo ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$implemento->status->cor()">{{ $implemento->status->rotulo() }}</flux:badge>
                        </flux:table.cell>
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
    @endif
</x-layouts::app>
