<x-formulario.secao titulo="Identificação" icone="home-modern" descricao="Nome da propriedade rural e de quem é o proprietário.">
    <div class="grid items-start gap-6 sm:grid-cols-2">
        <flux:input name="nome" label="Nome da propriedade" :value="old('nome', $propriedade->nome)" placeholder="Ex.: Fazenda Santa Helena" required autofocus />
        <flux:input name="proprietario" label="Proprietário" :value="old('proprietario', $propriedade->proprietario)" placeholder="Nome completo" required />
    </div>
</x-formulario.secao>

<x-formulario.secao titulo="Localização e área" icone="map-pin" descricao="A área total limita a soma das áreas dos talhões ativos da propriedade.">
    <div class="grid items-start gap-6 sm:grid-cols-3">
        <div class="sm:col-span-2">
            <flux:input name="municipio" label="Município" :value="old('municipio', $propriedade->municipio)" placeholder="Ex.: Bebedouro" required />
        </div>

        <flux:select name="uf" label="UF" placeholder="Selecione">
            @foreach (\App\Models\Propriedade::UFS as $uf)
                <flux:select.option :value="$uf" :selected="old('uf', $propriedade->uf) === $uf">{{ $uf }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <div class="grid items-start gap-6 sm:grid-cols-2">
        <flux:input.group label="Área total" name="area_total">
            <flux:input type="number" step="0.01" min="0" inputmode="decimal" name="area_total" :value="old('area_total', $propriedade->area_total)" placeholder="0,00" required />
            <flux:input.group.suffix>hectares</flux:input.group.suffix>
        </flux:input.group>
    </div>
</x-formulario.secao>

<x-formulario.secao titulo="Informações adicionais" icone="document-text" descricao="Anotações sobre a propriedade, como sede, acessos ou áreas de preservação.">
    <flux:textarea name="observacoes" label="Observações (opcional)" rows="4" placeholder="Ex.: a área restante corresponde à sede e às áreas de preservação.">{{ old('observacoes', $propriedade->observacoes) }}</flux:textarea>
</x-formulario.secao>

<x-formulario.acoes :cancelar="$propriedade->exists ? route('propriedades.show', $propriedade) : route('propriedades.index')" />
