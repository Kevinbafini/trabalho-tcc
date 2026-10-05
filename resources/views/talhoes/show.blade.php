<x-layouts::app :title="$talhao->nome">
    <x-cabecalho-pagina
        :titulo="$talhao->nome"
        :descricao="$talhao->propriedade->nome.' · '.$talhao->cultura.' · Safra '.$talhao->safra.' · '.Number::format($talhao->area, 2).' ha'"
        :voltar="route('talhoes.index')"
    >
        <x-slot:acoes>
            <flux:button :href="route('talhoes.edit', $talhao)" icon="pencil-square" wire:navigate>Editar</flux:button>
            <flux:button :href="route('atividades.create', ['propriedade_id' => $talhao->propriedade_id, 'talhao_id' => $talhao->id])" variant="primary" icon="plus" wire:navigate>Registrar atividade</flux:button>
        </x-slot:acoes>
    </x-cabecalho-pagina>

    <div class="mb-6 flex items-center gap-2">
        <flux:text>Situação:</flux:text>
        <flux:badge size="sm" :color="$talhao->status->cor()">{{ $talhao->status->rotulo() }}</flux:badge>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <x-indicador rotulo="Insumos" :valor="Number::currency($resumo['custo_insumos'])" />
        <x-indicador rotulo="Maquinário" :valor="Number::currency($resumo['custo_maquinas'])" />
        <x-indicador rotulo="Mão de obra" :valor="Number::currency($resumo['custo_mao_obra'])" />
        <x-indicador rotulo="Custo total" :valor="Number::currency($resumo['custo_total'])" :detalhe="$resumo['quantidade_atividades'].' atividade(s)'" />
        <x-indicador
            rotulo="Custo por hectare"
            :valor="$resumo['custo_por_hectare'] !== null ? Number::currency($resumo['custo_por_hectare']) : '—'"
            :detalhe="'Custo total ÷ '.Number::format($talhao->area, 2).' ha'"
        />
    </div>

    <div class="mt-8">
        <flux:heading size="lg" class="mb-3">Atividades que formam o custo</flux:heading>

        @if ($atividades->isEmpty())
            <x-estado-vazio icone="clipboard-document-list" titulo="Nenhuma atividade registrada" descricao="As atividades realizadas neste talhão e seus custos aparecerão aqui.">
                <flux:button :href="route('atividades.create', ['propriedade_id' => $talhao->propriedade_id, 'talhao_id' => $talhao->id])" variant="primary" icon="plus" wire:navigate>Registrar atividade</flux:button>
            </x-estado-vazio>
        @else
            @include('atividades.tabela', ['atividades' => $atividades, 'mostrarTalhao' => false])
        @endif
    </div>
</x-layouts::app>
