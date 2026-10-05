<x-formulario.secao titulo="Identificação" icone="beaker" descricao="Sementes, fertilizantes, corretivos e defensivos utilizados nas atividades.">
    @include('partials.campo-propriedade', ['registro' => $insumo])

    <flux:input name="nome" label="Nome" :value="old('nome', $insumo->nome)" placeholder="Ex.: Ureia" required />

    <div class="grid items-start gap-6 sm:grid-cols-2">
        <flux:select name="categoria" label="Categoria" placeholder="Selecione">
            @foreach (\App\Enums\CategoriaInsumo::cases() as $categoria)
                <flux:select.option :value="$categoria->value" :selected="old('categoria', $insumo->categoria?->value) === $categoria->value">{{ $categoria->rotulo() }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select name="unidade" label="Unidade de medida" placeholder="Selecione">
            @foreach (\App\Enums\UnidadeMedida::cases() as $unidade)
                <flux:select.option :value="$unidade->value" :selected="old('unidade', $insumo->unidade?->value) === $unidade->value">{{ $unidade->rotulo() }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>
</x-formulario.secao>

<x-formulario.secao titulo="Valor e estoque" icone="banknotes" descricao="Custo do insumo na atividade = quantidade × valor unitário. Atividades já registradas mantêm o valor do dia do lançamento.">
    <div class="grid items-start gap-6 sm:grid-cols-2">
        <flux:input.group label="Valor unitário" name="valor_unitario" description:trailing="Valor por unidade de medida (ex.: por kg).">
            <flux:input.group.prefix>R$</flux:input.group.prefix>
            <flux:input type="number" step="0.01" min="0" inputmode="decimal" name="valor_unitario" :value="old('valor_unitario', $insumo->valor_unitario)" placeholder="0,00" required />
        </flux:input.group>

        <flux:input
            type="number"
            step="0.001"
            min="0"
            inputmode="decimal"
            name="quantidade_estoque"
            label="Quantidade em estoque"
            description:trailing="Apenas informativo: não há baixa automática."
            :value="old('quantidade_estoque', $insumo->quantidade_estoque ?? 0)"
            required
        />
    </div>
</x-formulario.secao>

<x-formulario.secao titulo="Situação" icone="check-circle" descricao="Insumos inativos não aparecem para novas atividades, mas continuam no histórico.">
    @include('partials.campo-situacao', ['opcoes' => \App\Enums\StatusRecurso::cases(), 'selecionada' => $insumo->status?->value])
</x-formulario.secao>

<x-formulario.acoes :cancelar="route('insumos.index')" />
