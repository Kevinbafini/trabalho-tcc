{{--
    Formulário de atividade agrícola (cadastro e edição).
    O resumo de custos é uma prévia calculada no navegador; o valor definitivo
    é calculado no servidor pelo CustoAtividadeService ao salvar.
--}}
<div
    x-data="formularioAtividade(@js([...$formulario, 'erros' => (object) $errors->getMessages(), 'urlCadastro' => route('atividades.create')]))"
    class="grid gap-6 lg:grid-cols-3"
>
    @if ($errors->any())
        <flux:callout variant="danger" icon="exclamation-triangle" class="lg:col-span-3" heading="Não foi possível salvar a atividade. Verifique os campos destacados.">
            @error('recursos')
                <flux:callout.text>{{ $message }}</flux:callout.text>
            @enderror
        </flux:callout>
    @endif

    <div class="space-y-6 lg:col-span-2">
        <flux:card class="space-y-6">
            <flux:heading size="lg">1. Dados da atividade</flux:heading>

            @if ($atividade->exists)
                <flux:field>
                    <flux:label>Propriedade</flux:label>
                    <flux:text class="font-medium text-zinc-800 dark:text-white">{{ $propriedade->nome }}</flux:text>
                </flux:field>
            @elseif ($propriedades->count() > 1)
                <flux:select
                    name="propriedade_id"
                    label="Propriedade"
                    description="Ao trocar a propriedade, o formulário é recarregado com os talhões e recursos dela."
                    x-on:change="trocarPropriedade($event.target.value)"
                >
                    @foreach ($propriedades as $opcaoPropriedade)
                        <flux:select.option :value="$opcaoPropriedade->id" :selected="$opcaoPropriedade->id === $propriedade->id">{{ $opcaoPropriedade->nome }}</flux:select.option>
                    @endforeach
                </flux:select>
            @else
                <input type="hidden" name="propriedade_id" value="{{ $propriedade->id }}">
                <flux:field>
                    <flux:label>Propriedade</flux:label>
                    <flux:text class="font-medium text-zinc-800 dark:text-white">{{ $propriedade->nome }}</flux:text>
                </flux:field>
            @endif

            <div class="grid gap-6 sm:grid-cols-2">
                <flux:select name="talhao_id" label="Talhão" placeholder="Selecione o talhão">
                    @foreach ($talhoes as $talhao)
                        <flux:select.option :value="$talhao->id" :selected="$talhaoSelecionado === $talhao->id">
                            {{ $talhao->nome }} — {{ $talhao->cultura }} (safra {{ $talhao->safra }})
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input
                    type="date"
                    name="data"
                    label="Data"
                    :value="old('data', $atividade->data?->format('Y-m-d'))"
                    :max="today()->format('Y-m-d')"
                    required
                />
            </div>

            <flux:select name="tipo" label="Tipo de operação" placeholder="Selecione o tipo">
                @foreach (\App\Enums\TipoOperacao::cases() as $tipo)
                    <flux:select.option :value="$tipo->value" :selected="old('tipo', $atividade->tipo?->value) === $tipo->value">{{ $tipo->rotulo() }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:textarea name="descricao" label="Descrição (opcional)" rows="2" placeholder="Ex.: Aplicação de herbicida pós-emergente">{{ old('descricao', $atividade->descricao) }}</flux:textarea>

            <flux:text class="text-sm">
                @if ($atividade->exists)
                    Safra registrada: <strong>{{ $atividade->safra }}</strong> (copiada do talhão no lançamento; não muda se a safra do talhão for alterada).
                @else
                    A safra é copiada automaticamente do talhão selecionado.
                @endif
            </flux:text>
        </flux:card>

        @include('atividades.partials.recursos', [
            'tipo' => 'insumos',
            'titulo' => '2. Insumos',
            'ajuda' => 'Custo = quantidade × valor unitário.',
            'campoRecurso' => 'insumo_id',
            'campoQuantidade' => 'quantidade',
            'rotuloRecurso' => 'Insumo',
            'rotuloQuantidade' => 'Quantidade',
            'textoBotao' => 'Adicionar insumo',
            'textoVazio' => 'Nenhum insumo adicionado.',
            'opcoes' => $formulario['opcoes']['insumos'],
            'rotaCadastro' => route('insumos.create', ['propriedade_id' => $propriedade->id]),
        ])

        @include('atividades.partials.recursos', [
            'tipo' => 'maquinas',
            'titulo' => '3. Máquinas',
            'ajuda' => 'Custo = horas de uso × custo por hora.',
            'campoRecurso' => 'maquina_id',
            'campoQuantidade' => 'horas',
            'rotuloRecurso' => 'Máquina',
            'rotuloQuantidade' => 'Horas de uso',
            'textoBotao' => 'Adicionar máquina',
            'textoVazio' => 'Nenhuma máquina adicionada.',
            'opcoes' => $formulario['opcoes']['maquinas'],
            'rotaCadastro' => route('maquinas.create', ['propriedade_id' => $propriedade->id]),
        ])

        @include('atividades.partials.recursos', [
            'tipo' => 'funcionarios',
            'titulo' => '4. Mão de obra',
            'ajuda' => 'Custo = horas trabalhadas × custo por hora.',
            'campoRecurso' => 'funcionario_id',
            'campoQuantidade' => 'horas',
            'rotuloRecurso' => 'Funcionário',
            'rotuloQuantidade' => 'Horas trabalhadas',
            'textoBotao' => 'Adicionar funcionário',
            'textoVazio' => 'Nenhum funcionário adicionado.',
            'opcoes' => $formulario['opcoes']['funcionarios'],
            'rotaCadastro' => route('funcionarios.create', ['propriedade_id' => $propriedade->id]),
        ])

        <flux:card class="space-y-4">
            <div>
                <flux:heading size="lg">5. Implementos (opcional)</flux:heading>
                <flux:text class="mt-1 text-sm">Implementos não têm custo próprio: o custo deles já está incluído no custo por hora da máquina.</flux:text>
            </div>

            @if ($implementos->isEmpty())
                <flux:text class="text-sm">Nenhum implemento ativo nesta propriedade.</flux:text>
            @else
                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach ($implementos as $implemento)
                        <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                            <input
                                type="checkbox"
                                name="implementos[]"
                                value="{{ $implemento->id }}"
                                @checked(in_array($implemento->id, $implementosSelecionados, true))
                                class="size-4 rounded border-zinc-300 accent-zinc-800 dark:accent-white"
                            >
                            {{ $implemento->nome }}
                            <span class="text-zinc-400">· {{ $implemento->tipo }}</span>
                        </label>
                    @endforeach
                </div>
            @endif
        </flux:card>
    </div>

    <div>
        <flux:card class="space-y-4 lg:sticky lg:top-6">
            <flux:heading size="lg">Resumo do custo</flux:heading>

            <dl class="space-y-2 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-zinc-500 dark:text-zinc-400">Custo dos insumos</dt>
                    <dd class="font-medium tabular-nums text-zinc-800 dark:text-white" x-text="moeda(subtotal('insumos'))"></dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-zinc-500 dark:text-zinc-400">Custo de máquinas</dt>
                    <dd class="font-medium tabular-nums text-zinc-800 dark:text-white" x-text="moeda(subtotal('maquinas'))"></dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-zinc-500 dark:text-zinc-400">Custo de mão de obra</dt>
                    <dd class="font-medium tabular-nums text-zinc-800 dark:text-white" x-text="moeda(subtotal('funcionarios'))"></dd>
                </div>
            </dl>

            <flux:separator />

            <div class="flex items-baseline justify-between gap-4">
                <flux:text class="font-medium">Custo total da atividade</flux:text>
                <flux:heading size="xl" class="tabular-nums" x-text="moeda(total)"></flux:heading>
            </div>

            <flux:text class="text-xs">
                Prévia calculada durante o preenchimento. Ao salvar, o sistema recalcula e grava os custos com os valores do dia.
            </flux:text>

            <div class="flex flex-col gap-2">
                <flux:button type="submit" variant="primary" icon="check" class="w-full">Salvar atividade</flux:button>
                <flux:button :href="$atividade->exists ? route('atividades.show', $atividade) : route('atividades.index')" variant="ghost" class="w-full" wire:navigate>Cancelar</flux:button>
            </div>
        </flux:card>
    </div>
</div>
