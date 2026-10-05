<x-layouts::app :title="$atividade->tipo->rotulo().' — '.$atividade->data->format('d/m/Y')">
    <x-cabecalho-pagina
        :titulo="$atividade->tipo->rotulo().' em '.$atividade->data->format('d/m/Y')"
        :descricao="$atividade->propriedade->nome.' · '.$atividade->talhao->nome.' · Safra '.$atividade->safra"
        :voltar="route('atividades.index')"
    >
        <x-slot:acoes>
            <flux:button :href="route('atividades.edit', $atividade)" icon="pencil-square" wire:navigate>Editar</flux:button>
            <x-confirmar-exclusao
                :acao="route('atividades.destroy', $atividade)"
                :nome="'atividade-'.$atividade->id"
                mensagem="Deseja excluir esta atividade? Os custos dela deixarão de compor os custos do talhão."
            />
        </x-slot:acoes>
    </x-cabecalho-pagina>

    @if ($atividade->descricao)
        <flux:text class="mb-6 whitespace-pre-line">{{ $atividade->descricao }}</flux:text>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-indicador rotulo="Custo dos insumos" :valor="Number::currency($atividade->custo_insumos)" />
        <x-indicador rotulo="Custo de máquinas" :valor="Number::currency($atividade->custo_maquinas)" />
        <x-indicador rotulo="Custo de mão de obra" :valor="Number::currency($atividade->custo_mao_obra)" />
        <x-indicador rotulo="Custo total" :valor="Number::currency($atividade->custo_total)" />
    </div>

    <flux:callout icon="information-circle" class="mt-6">
        <flux:callout.text>
            Os valores unitários abaixo foram gravados no dia do lançamento. Alterações posteriores nos cadastros não mudam o custo desta atividade.
        </flux:callout.text>
    </flux:callout>

    <div class="mt-8 flex items-center justify-between gap-4">
        <flux:heading size="lg">Recursos utilizados</flux:heading>
        <x-alternador-visualizacao />
    </div>

    <div class="mt-4 space-y-8">
        <section>
            <flux:heading class="mb-3">Insumos</flux:heading>
            @if ($atividade->insumos->isEmpty())
                <flux:text>Nenhum insumo utilizado.</flux:text>
            @else
                <x-lista-responsiva :alternador="false">
                    <x-slot:tabela>
                        <flux:table>
                            <flux:table.columns>
                                <flux:table.column>Insumo</flux:table.column>
                                <flux:table.column align="end">Quantidade</flux:table.column>
                                <flux:table.column align="end">Custo unitário</flux:table.column>
                                <flux:table.column align="end">Custo total</flux:table.column>
                            </flux:table.columns>
                            <flux:table.rows>
                                @foreach ($atividade->insumos as $insumo)
                                    <flux:table.row :key="$insumo->id">
                                        <flux:table.cell variant="strong">{{ $insumo->nome }}</flux:table.cell>
                                        <flux:table.cell align="end" class="tabular-nums">{{ Number::format($insumo->pivot->quantidade, maxPrecision: 3) }} {{ $insumo->unidade->value }}</flux:table.cell>
                                        <flux:table.cell align="end" class="tabular-nums">{{ Number::currency($insumo->pivot->custo_unitario) }}/{{ $insumo->unidade->value }}</flux:table.cell>
                                        <flux:table.cell align="end" variant="strong" class="tabular-nums">{{ Number::currency($insumo->pivot->custo_total) }}</flux:table.cell>
                                    </flux:table.row>
                                @endforeach
                            </flux:table.rows>
                        </flux:table>
                    </x-slot:tabela>

                    <x-slot:cartoes>
                        @foreach ($atividade->insumos as $insumo)
                            <x-cartao :titulo="$insumo->nome">
                                <x-cartao.campo rotulo="Quantidade">{{ Number::format($insumo->pivot->quantidade, maxPrecision: 3) }} {{ $insumo->unidade->value }}</x-cartao.campo>
                                <x-cartao.campo rotulo="Custo unitário">{{ Number::currency($insumo->pivot->custo_unitario) }}/{{ $insumo->unidade->value }}</x-cartao.campo>
                                <x-cartao.campo rotulo="Custo total" :destaque="true">{{ Number::currency($insumo->pivot->custo_total) }}</x-cartao.campo>
                            </x-cartao>
                        @endforeach
                    </x-slot:cartoes>
                </x-lista-responsiva>
            @endif
        </section>

        <section>
            <flux:heading class="mb-3">Máquinas</flux:heading>
            @if ($atividade->maquinas->isEmpty())
                <flux:text>Nenhuma máquina utilizada.</flux:text>
            @else
                <x-lista-responsiva :alternador="false">
                    <x-slot:tabela>
                        <flux:table>
                            <flux:table.columns>
                                <flux:table.column>Máquina</flux:table.column>
                                <flux:table.column align="end">Horas</flux:table.column>
                                <flux:table.column align="end">Custo por hora</flux:table.column>
                                <flux:table.column align="end">Custo total</flux:table.column>
                            </flux:table.columns>
                            <flux:table.rows>
                                @foreach ($atividade->maquinas as $maquina)
                                    <flux:table.row :key="$maquina->id">
                                        <flux:table.cell variant="strong">{{ $maquina->nome }}</flux:table.cell>
                                        <flux:table.cell align="end" class="tabular-nums">{{ Number::format($maquina->pivot->horas, maxPrecision: 2) }} h</flux:table.cell>
                                        <flux:table.cell align="end" class="tabular-nums">{{ Number::currency($maquina->pivot->custo_hora) }}/h</flux:table.cell>
                                        <flux:table.cell align="end" variant="strong" class="tabular-nums">{{ Number::currency($maquina->pivot->custo_total) }}</flux:table.cell>
                                    </flux:table.row>
                                @endforeach
                            </flux:table.rows>
                        </flux:table>
                    </x-slot:tabela>

                    <x-slot:cartoes>
                        @foreach ($atividade->maquinas as $maquina)
                            <x-cartao :titulo="$maquina->nome">
                                <x-cartao.campo rotulo="Horas">{{ Number::format($maquina->pivot->horas, maxPrecision: 2) }} h</x-cartao.campo>
                                <x-cartao.campo rotulo="Custo por hora">{{ Number::currency($maquina->pivot->custo_hora) }}/h</x-cartao.campo>
                                <x-cartao.campo rotulo="Custo total" :destaque="true">{{ Number::currency($maquina->pivot->custo_total) }}</x-cartao.campo>
                            </x-cartao>
                        @endforeach
                    </x-slot:cartoes>
                </x-lista-responsiva>
            @endif
        </section>

        <section>
            <flux:heading class="mb-3">Mão de obra</flux:heading>
            @if ($atividade->funcionarios->isEmpty())
                <flux:text>Nenhum funcionário informado.</flux:text>
            @else
                <x-lista-responsiva :alternador="false">
                    <x-slot:tabela>
                        <flux:table>
                            <flux:table.columns>
                                <flux:table.column>Funcionário</flux:table.column>
                                <flux:table.column>Função</flux:table.column>
                                <flux:table.column align="end">Horas</flux:table.column>
                                <flux:table.column align="end">Custo por hora</flux:table.column>
                                <flux:table.column align="end">Custo total</flux:table.column>
                            </flux:table.columns>
                            <flux:table.rows>
                                @foreach ($atividade->funcionarios as $funcionario)
                                    <flux:table.row :key="$funcionario->id">
                                        <flux:table.cell variant="strong">{{ $funcionario->nome }}</flux:table.cell>
                                        <flux:table.cell>{{ $funcionario->funcao }}</flux:table.cell>
                                        <flux:table.cell align="end" class="tabular-nums">{{ Number::format($funcionario->pivot->horas, maxPrecision: 2) }} h</flux:table.cell>
                                        <flux:table.cell align="end" class="tabular-nums">{{ Number::currency($funcionario->pivot->custo_hora) }}/h</flux:table.cell>
                                        <flux:table.cell align="end" variant="strong" class="tabular-nums">{{ Number::currency($funcionario->pivot->custo_total) }}</flux:table.cell>
                                    </flux:table.row>
                                @endforeach
                            </flux:table.rows>
                        </flux:table>
                    </x-slot:tabela>

                    <x-slot:cartoes>
                        @foreach ($atividade->funcionarios as $funcionario)
                            <x-cartao :titulo="$funcionario->nome" :subtitulo="$funcionario->funcao">
                                <x-cartao.campo rotulo="Horas">{{ Number::format($funcionario->pivot->horas, maxPrecision: 2) }} h</x-cartao.campo>
                                <x-cartao.campo rotulo="Custo por hora">{{ Number::currency($funcionario->pivot->custo_hora) }}/h</x-cartao.campo>
                                <x-cartao.campo rotulo="Custo total" :destaque="true">{{ Number::currency($funcionario->pivot->custo_total) }}</x-cartao.campo>
                            </x-cartao>
                        @endforeach
                    </x-slot:cartoes>
                </x-lista-responsiva>
            @endif
        </section>

        <section>
            <flux:heading class="mb-3">Implementos</flux:heading>
            @if ($atividade->implementos->isEmpty())
                <flux:text>Nenhum implemento associado.</flux:text>
            @else
                <div class="flex flex-wrap gap-2">
                    @foreach ($atividade->implementos as $implemento)
                        <flux:badge>{{ $implemento->nome }}</flux:badge>
                    @endforeach
                </div>
                <flux:text class="mt-2 text-sm">Sem custo próprio: incluído no custo por hora das máquinas.</flux:text>
            @endif
        </section>
    </div>
</x-layouts::app>
