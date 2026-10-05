<div class="space-y-6">
    <flux:input name="nome" label="Nome da propriedade" :value="old('nome', $propriedade->nome)" placeholder="Ex.: Fazenda Santa Helena" required autofocus />

    <flux:input name="proprietario" label="Proprietário" :value="old('proprietario', $propriedade->proprietario)" required />

    <div class="grid gap-6 sm:grid-cols-3">
        <div class="sm:col-span-2">
            <flux:input name="municipio" label="Município" :value="old('municipio', $propriedade->municipio)" required />
        </div>

        <flux:select name="uf" label="UF" placeholder="Selecione">
            @foreach (\App\Models\Propriedade::UFS as $uf)
                <flux:select.option :value="$uf" :selected="old('uf', $propriedade->uf) === $uf">{{ $uf }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <flux:input
        type="number"
        step="0.01"
        min="0"
        inputmode="decimal"
        name="area_total"
        label="Área total (hectares)"
        description="A soma das áreas dos talhões ativos não poderá ultrapassar este valor."
        :value="old('area_total', $propriedade->area_total)"
        required
    />

    <flux:textarea name="observacoes" label="Observações (opcional)" rows="3">{{ old('observacoes', $propriedade->observacoes) }}</flux:textarea>

    @include('partials.botoes-formulario', ['cancelar' => $propriedade->exists ? route('propriedades.show', $propriedade) : route('propriedades.index')])
</div>
