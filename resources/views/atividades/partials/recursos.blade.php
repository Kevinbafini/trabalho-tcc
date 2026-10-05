{{--
    Seção dinâmica do formulário de atividade para um tipo de recurso
    (insumos, máquinas ou funcionários). As linhas são controladas pelo Alpine.js.

    Variáveis: $tipo, $titulo, $ajuda, $campoRecurso, $campoQuantidade,
    $rotuloRecurso, $rotuloQuantidade, $textoBotao, $textoVazio, $opcoes, $rotaCadastro
--}}
<flux:card class="space-y-4">
    <div class="flex items-start justify-between gap-4">
        <div>
            <flux:heading size="lg">{{ $titulo }}</flux:heading>
            <flux:text class="mt-1 text-sm">{{ $ajuda }}</flux:text>
        </div>

        <div class="text-end">
            <flux:text class="text-xs">Subtotal</flux:text>
            <flux:heading class="tabular-nums" x-text="moeda(subtotal('{{ $tipo }}'))"></flux:heading>
        </div>
    </div>

    @if (empty($opcoes))
        <flux:callout variant="warning" icon="exclamation-triangle">
            <flux:callout.text>
                Não há {{ mb_strtolower($rotuloRecurso) }}s ativos nesta propriedade.
                <flux:link :href="$rotaCadastro">Cadastrar agora</flux:link>
            </flux:callout.text>
        </flux:callout>
    @endif

    <p class="text-sm text-zinc-500 dark:text-zinc-400" x-show="linhas.{{ $tipo }}.length === 0">{{ $textoVazio }}</p>

    <template x-for="(linha, indice) in linhas.{{ $tipo }}" :key="linha.chave">
        <div class="grid gap-3 rounded-lg border border-zinc-200 p-3 sm:grid-cols-12 sm:items-start dark:border-zinc-700">
            <div class="sm:col-span-6">
                <flux:select
                    x-model="linha.recurso"
                    x-bind:name="`{{ $tipo }}[${indice}][{{ $campoRecurso }}]`"
                    label="{{ $rotuloRecurso }}"
                    placeholder="Selecione"
                >
                    @foreach ($opcoes as $opcao)
                        <option value="{{ $opcao['id'] }}">{{ $opcao['nome'] }}</option>
                    @endforeach
                </flux:select>
                <p class="mt-1 text-sm text-red-600 dark:text-red-400" x-show="erro(`{{ $tipo }}.${indice}.{{ $campoRecurso }}`)" x-text="erro(`{{ $tipo }}.${indice}.{{ $campoRecurso }}`)"></p>
            </div>

            <div class="sm:col-span-3">
                <flux:input
                    type="number"
                    step="0.01"
                    min="0"
                    inputmode="decimal"
                    x-model="linha.quantidade"
                    x-bind:name="`{{ $tipo }}[${indice}][{{ $campoQuantidade }}]`"
                    label="{{ $rotuloQuantidade }}"
                />
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400" x-show="unidade('{{ $tipo }}', linha)" x-text="'em ' + unidade('{{ $tipo }}', linha)"></p>
                <p class="mt-1 text-sm text-red-600 dark:text-red-400" x-show="erro(`{{ $tipo }}.${indice}.{{ $campoQuantidade }}`)" x-text="erro(`{{ $tipo }}.${indice}.{{ $campoQuantidade }}`)"></p>
            </div>

            <div class="flex items-start justify-between gap-2 sm:col-span-3 sm:pt-6">
                <div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400" x-text="descricaoDoValor('{{ $tipo }}', linha)"></p>
                    <p class="font-medium tabular-nums text-zinc-800 dark:text-white" x-text="moeda(custoDaLinha('{{ $tipo }}', linha))"></p>
                </div>

                <flux:button variant="ghost" size="sm" icon="trash" x-on:click="remover('{{ $tipo }}', indice)" aria-label="Remover linha" />
            </div>
        </div>
    </template>

    @unless (empty($opcoes))
        <flux:button variant="filled" size="sm" icon="plus" x-on:click="adicionar('{{ $tipo }}')">{{ $textoBotao }}</flux:button>
    @endunless
</flux:card>
