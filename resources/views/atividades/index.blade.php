<x-layouts::app title="Atividades agrícolas">
    <x-cabecalho-pagina titulo="Atividades agrícolas" icone="clipboard-document-list" descricao="Operações realizadas nos talhões e o custo apropriado a cada uma.">
        <x-slot:acoes>
            <flux:button :href="route('atividades.create', array_filter(['propriedade_id' => $filtros['propriedade_id']]))" variant="primary" icon="plus" wire:navigate>Registrar atividade</flux:button>
        </x-slot:acoes>
    </x-cabecalho-pagina>

    <x-busca>
        <x-slot:filtros>
            <x-busca.campo placeholder="Buscar por operação, talhão, descrição ou safra..." :valor="$busca" />

            @if ($propriedades->count() > 1)
                <x-busca.filtro nome="propriedade_id" rotulo="Propriedade">
                    <flux:select.option value="">Todas as propriedades</flux:select.option>
                    @foreach ($propriedades as $propriedade)
                        <flux:select.option :value="$propriedade->id" :selected="$filtros['propriedade_id'] === $propriedade->id">{{ $propriedade->nome }}</flux:select.option>
                    @endforeach
                </x-busca.filtro>
            @endif

            <x-busca.filtro nome="talhao_id" rotulo="Talhão">
                <flux:select.option value="">Todos os talhões</flux:select.option>
                @foreach ($talhoes as $talhao)
                    <flux:select.option :value="$talhao->id" :selected="$filtros['talhao_id'] === $talhao->id">
                        {{ $talhao->nome }}{{ $propriedades->count() > 1 ? ' ('.$talhao->propriedade->nome.')' : '' }}
                    </flux:select.option>
                @endforeach
            </x-busca.filtro>

            <x-busca.filtro nome="tipo" rotulo="Operação">
                <flux:select.option value="">Todas as operações</flux:select.option>
                @foreach (\App\Enums\TipoOperacao::cases() as $tipo)
                    <flux:select.option :value="$tipo->value" :selected="$filtros['tipo'] === $tipo">{{ $tipo->rotulo() }}</flux:select.option>
                @endforeach
            </x-busca.filtro>
        </x-slot:filtros>

        @fragment('resultados')
            @if ($atividades->isEmpty() && ($busca !== '' || array_filter($filtros)))
                <x-busca.sem-resultados :busca="$busca" />
            @elseif ($atividades->isEmpty())
                <x-estado-vazio icone="clipboard-document-list" titulo="Nenhuma atividade registrada" descricao="Registre as operações realizadas nos talhões para que o sistema calcule os custos.">
                    <flux:button :href="route('atividades.create')" variant="primary" icon="plus" wire:navigate>Registrar atividade</flux:button>
                </x-estado-vazio>
            @else
                <x-resumo-da-lista :total="$atividades->total()" singular="atividade" plural="atividades">
                    custo total de <span class="font-semibold text-zinc-800 tabular-nums dark:text-white">{{ Number::currency($custoEncontrado) }}</span>
                </x-resumo-da-lista>

                @include('atividades.tabela', ['atividades' => $atividades, 'mostrarTalhao' => true, 'alternador' => false])
            @endif
        @endfragment
    </x-busca>
</x-layouts::app>
