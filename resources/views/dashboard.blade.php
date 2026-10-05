<x-layouts::app title="Dashboard">
    <x-cabecalho-pagina titulo="Dashboard" icone="chart-pie" descricao="Visão geral das atividades e dos custos das suas propriedades." />

    @if ($propriedades->isEmpty())
        <x-estado-vazio icone="home-modern" titulo="Bem-vindo! Comece cadastrando sua propriedade" descricao="O fluxo do sistema é: propriedade → talhões → recursos (funcionários, máquinas, implementos e insumos) → atividades agrícolas. Os indicadores de custos aparecem aqui automaticamente.">
            <flux:button :href="route('propriedades.create')" variant="primary" icon="plus" wire:navigate>Cadastrar propriedade</flux:button>
        </x-estado-vazio>
    @else
        <x-filtro-propriedade :propriedades="$propriedades" :selecionada="$propriedadeSelecionada" />

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <x-indicador rotulo="Área total" :valor="Number::format($areaTotal, 2).' ha'" />
            <x-indicador rotulo="Talhões" :valor="$quantidadeTalhoes" />
            <x-indicador rotulo="Atividades" :valor="$resumo['quantidade_atividades']" />
            <x-indicador rotulo="Custo total" :valor="Number::currency($resumo['custo_total'])" />
            <x-indicador
                rotulo="Custo médio por hectare"
                :valor="$resumo['custo_por_hectare'] !== null ? Number::currency($resumo['custo_por_hectare']) : '—'"
                :detalhe="'Sobre '.Number::format($resumo['area'], 2).' ha de talhões com atividades'"
            />
        </div>

        @if ($resumo['quantidade_atividades'] === 0)
            <x-estado-vazio class="mt-6" icone="clipboard-document-list" titulo="Nenhuma atividade registrada" descricao="Registre atividades nos talhões para ver a composição dos custos e o custo por hectare.">
                <flux:button :href="route('atividades.create')" variant="primary" icon="plus" wire:navigate>Registrar atividade</flux:button>
            </x-estado-vazio>
        @else
            <div class="mt-6 grid gap-6 lg:grid-cols-2">
                <flux:card class="space-y-4">
                    <div>
                        <flux:heading size="lg">Composição dos custos</flux:heading>
                        <flux:text class="text-sm">Participação de cada grupo no custo total.</flux:text>
                    </div>

                    <div class="h-10">
                        <canvas
                            wire:ignore
                            x-data="graficoComposicao(@js($graficoComposicao))"
                            role="img"
                            aria-label="Composição dos custos: insumos {{ Number::currency($resumo['custo_insumos']) }}, maquinário {{ Number::currency($resumo['custo_maquinas']) }}, mão de obra {{ Number::currency($resumo['custo_mao_obra']) }}"
                        ></canvas>
                    </div>

                    {{-- Legenda com valores (as cores nunca aparecem sozinhas). --}}
                    <ul class="space-y-2 text-sm">
                        @foreach ([
                            ['Insumos', $resumo['custo_insumos'], 'bg-[#2a78d6] dark:bg-[#3987e5]'],
                            ['Maquinário', $resumo['custo_maquinas'], 'bg-[#eb6834] dark:bg-[#d95926]'],
                            ['Mão de obra', $resumo['custo_mao_obra'], 'bg-[#1baf7a] dark:bg-[#199e70]'],
                        ] as [$rotulo, $valor, $cor])
                            <li class="flex items-center justify-between gap-4">
                                <span class="flex items-center gap-2 text-zinc-600 dark:text-zinc-300">
                                    <span class="size-3 rounded-sm {{ $cor }}"></span>
                                    {{ $rotulo }}
                                </span>
                                <span class="tabular-nums text-zinc-800 dark:text-white">
                                    {{ Number::currency($valor) }}
                                    <span class="text-zinc-500 dark:text-zinc-400">({{ Number::percentage($resumo['custo_total'] > 0 ? $valor / $resumo['custo_total'] * 100 : 0, 1) }})</span>
                                </span>
                            </li>
                        @endforeach

                        <li class="flex items-center justify-between gap-4 border-t border-zinc-200 pt-2 font-medium dark:border-zinc-700">
                            <span class="text-zinc-800 dark:text-white">Total</span>
                            <span class="tabular-nums text-zinc-800 dark:text-white">{{ Number::currency($resumo['custo_total']) }}</span>
                        </li>
                    </ul>
                </flux:card>

                <flux:card class="space-y-4">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <flux:heading size="lg">Custo por hectare dos talhões</flux:heading>
                            <flux:text class="text-sm">Custo total do talhão ÷ área do talhão.</flux:text>
                        </div>
                        <flux:link :href="route('custos-talhoes.index', array_filter(['propriedade_id' => $propriedadeSelecionada]))" class="text-sm" wire:navigate>Ver tabela</flux:link>
                    </div>

                    <div style="height: {{ max(160, count($graficoCustoPorHectare['rotulos']) * 36 + 40) }}px">
                        <canvas
                            wire:ignore
                            x-data="graficoCustoPorHectare(@js($graficoCustoPorHectare))"
                            role="img"
                            aria-label="Gráfico de barras com o custo por hectare de cada talhão. Os valores estão na tela Custos por Talhão."
                        ></canvas>
                    </div>
                </flux:card>
            </div>

            <div class="mt-8">
                <div class="mb-3 flex items-center justify-between gap-4">
                    <flux:heading size="lg">Últimas atividades</flux:heading>
                    <div class="flex items-center gap-4">
                        <x-alternador-visualizacao />
                        <flux:link :href="route('atividades.index', array_filter(['propriedade_id' => $propriedadeSelecionada]))" class="text-sm" wire:navigate>Ver todas</flux:link>
                    </div>
                </div>

                @include('atividades.tabela', ['atividades' => $ultimasAtividades, 'mostrarTalhao' => true, 'alternador' => false])
            </div>
        @endif
    @endif
</x-layouts::app>
