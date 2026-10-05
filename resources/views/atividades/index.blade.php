<x-layouts::app title="Atividades agrícolas">
    <x-cabecalho-pagina titulo="Atividades agrícolas" descricao="Operações realizadas nos talhões e o custo apropriado a cada uma.">
        <x-slot:acoes>
            <flux:button :href="route('atividades.create', array_filter(['propriedade_id' => $filtros['propriedade_id']]))" variant="primary" icon="plus" wire:navigate>Registrar atividade</flux:button>
        </x-slot:acoes>
    </x-cabecalho-pagina>

    <form method="GET" class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4 lg:items-end">
        @if ($propriedades->count() > 1)
            <flux:select name="propriedade_id" label="Propriedade">
                <flux:select.option value="">Todas</flux:select.option>
                @foreach ($propriedades as $propriedade)
                    <flux:select.option :value="$propriedade->id" :selected="$filtros['propriedade_id'] === $propriedade->id">{{ $propriedade->nome }}</flux:select.option>
                @endforeach
            </flux:select>
        @endif

        <flux:select name="talhao_id" label="Talhão">
            <flux:select.option value="">Todos</flux:select.option>
            @foreach ($talhoes as $talhao)
                <flux:select.option :value="$talhao->id" :selected="$filtros['talhao_id'] === $talhao->id">
                    {{ $talhao->nome }}{{ $propriedades->count() > 1 ? ' ('.$talhao->propriedade->nome.')' : '' }}
                </flux:select.option>
            @endforeach
        </flux:select>

        <flux:select name="tipo" label="Operação">
            <flux:select.option value="">Todas</flux:select.option>
            @foreach (\App\Enums\TipoOperacao::cases() as $tipo)
                <flux:select.option :value="$tipo->value" :selected="$filtros['tipo'] === $tipo">{{ $tipo->rotulo() }}</flux:select.option>
            @endforeach
        </flux:select>

        <div class="flex gap-2">
            <flux:button type="submit" icon="funnel">Filtrar</flux:button>
            @if (array_filter($filtros))
                <flux:button :href="route('atividades.index')" variant="ghost" wire:navigate>Limpar</flux:button>
            @endif
        </div>
    </form>

    @if ($atividades->isEmpty())
        @if (array_filter($filtros))
            <x-estado-vazio icone="funnel" titulo="Nenhuma atividade encontrada" descricao="Não há atividades para os filtros selecionados." />
        @else
            <x-estado-vazio icone="clipboard-document-list" titulo="Nenhuma atividade registrada" descricao="Registre as operações realizadas nos talhões para que o sistema calcule os custos.">
                <flux:button :href="route('atividades.create')" variant="primary" icon="plus" wire:navigate>Registrar atividade</flux:button>
            </x-estado-vazio>
        @endif
    @else
        @include('atividades.tabela', ['atividades' => $atividades, 'mostrarTalhao' => true])
    @endif
</x-layouts::app>
