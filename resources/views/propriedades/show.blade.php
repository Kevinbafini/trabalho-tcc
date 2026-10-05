<x-layouts::app :title="$propriedade->nome">
    <x-cabecalho-pagina
        :titulo="$propriedade->nome"
        :descricao="$propriedade->municipio.'/'.$propriedade->uf.' · Proprietário: '.$propriedade->proprietario"
        :voltar="route('propriedades.index')"
    >
        <x-slot:acoes>
            <flux:button :href="route('propriedades.edit', $propriedade)" icon="pencil-square" wire:navigate>Editar</flux:button>
            <flux:button :href="route('talhoes.create', ['propriedade_id' => $propriedade->id])" variant="primary" icon="plus" wire:navigate>Novo talhão</flux:button>
        </x-slot:acoes>
    </x-cabecalho-pagina>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-indicador rotulo="Área total" :valor="Number::format($propriedade->area_total, 2).' ha'" :detalhe="'Ocupada por talhões ativos: '.Number::format($areaOcupada, 2).' ha'" />
        <x-indicador rotulo="Atividades registradas" :valor="$resumo['quantidade_atividades']" />
        <x-indicador rotulo="Custo total" :valor="Number::currency($resumo['custo_total'])" />
        <x-indicador
            rotulo="Custo médio por hectare"
            :valor="$resumo['custo_por_hectare'] !== null ? Number::currency($resumo['custo_por_hectare']) : '—'"
            detalhe="Custo total ÷ área dos talhões com atividades"
        />
    </div>

    <div class="mt-8">
        <flux:heading size="lg" class="mb-3">Talhões e custos</flux:heading>

        @if ($custosPorTalhao->isEmpty())
            <x-estado-vazio icone="map" titulo="Nenhum talhão cadastrado" descricao="Divida a propriedade em talhões para registrar as atividades e acompanhar o custo por hectare.">
                <flux:button :href="route('talhoes.create', ['propriedade_id' => $propriedade->id])" variant="primary" icon="plus" wire:navigate>Cadastrar talhão</flux:button>
            </x-estado-vazio>
        @else
            @include('custos-talhoes.tabela', ['custosPorTalhao' => $custosPorTalhao, 'mostrarPropriedade' => false])
        @endif
    </div>

    <div class="mt-8">
        <flux:heading size="lg" class="mb-3">Recursos da propriedade</flux:heading>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['Funcionários', $propriedade->funcionarios_count, 'funcionarios.index', 'users'],
                ['Máquinas', $propriedade->maquinas_count, 'maquinas.index', 'truck'],
                ['Implementos', $propriedade->implementos_count, 'implementos.index', 'wrench-screwdriver'],
                ['Insumos', $propriedade->insumos_count, 'insumos.index', 'beaker'],
            ] as [$rotulo, $quantidade, $rota, $icone])
                <a href="{{ route($rota, ['propriedade_id' => $propriedade->id]) }}" wire:navigate class="block rounded-xl border border-zinc-200 p-4 transition hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-white/5">
                    <div class="flex items-center gap-3">
                        <flux:icon :name="$icone" class="size-5 text-zinc-400" />
                        <flux:text>{{ $rotulo }}</flux:text>
                    </div>
                    <flux:heading size="xl" class="mt-2 tabular-nums">{{ $quantidade }}</flux:heading>
                </a>
            @endforeach
        </div>
    </div>

    @if ($propriedade->observacoes)
        <div class="mt-8">
            <flux:heading size="lg" class="mb-2">Observações</flux:heading>
            <flux:text class="whitespace-pre-line">{{ $propriedade->observacoes }}</flux:text>
        </div>
    @endif
</x-layouts::app>
