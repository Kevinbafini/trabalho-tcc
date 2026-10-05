<div class="space-y-6">
    @include('partials.campo-propriedade', ['registro' => $insumo])

    <flux:input name="nome" label="Nome" :value="old('nome', $insumo->nome)" placeholder="Ex.: Ureia" required />

    <div class="grid gap-6 sm:grid-cols-2">
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

    <div class="grid gap-6 sm:grid-cols-2">
        <flux:input
            type="number"
            step="0.01"
            min="0"
            inputmode="decimal"
            name="valor_unitario"
            label="Valor unitário (R$ por unidade)"
            description="Atividades já registradas mantêm o valor do dia do lançamento."
            :value="old('valor_unitario', $insumo->valor_unitario)"
            required
        />

        <flux:input
            type="number"
            step="0.001"
            min="0"
            inputmode="decimal"
            name="quantidade_estoque"
            label="Quantidade em estoque"
            description="Apenas informativo: não há baixa automática."
            :value="old('quantidade_estoque', $insumo->quantidade_estoque ?? 0)"
            required
        />
    </div>

    <flux:select name="status" label="Situação" description="Insumos inativos não aparecem para novas atividades.">
        @foreach (\App\Enums\StatusRecurso::cases() as $status)
            <flux:select.option :value="$status->value" :selected="old('status', $insumo->status?->value) === $status->value">{{ $status->rotulo() }}</flux:select.option>
        @endforeach
    </flux:select>

    @include('partials.botoes-formulario', ['cancelar' => route('insumos.index')])
</div>
