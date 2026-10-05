<x-formulario.secao titulo="Identificação" icone="map" descricao="O custo por hectare do talhão é calculado sobre a área informada aqui.">
    @include('partials.campo-propriedade', ['registro' => $talhao])

    <div class="grid items-start gap-6 sm:grid-cols-2">
        <flux:input name="nome" label="Nome do talhão" :value="old('nome', $talhao->nome)" placeholder="Ex.: Talhão 01" required />

        <flux:input.group label="Área" name="area">
            <flux:input type="number" step="0.01" min="0" inputmode="decimal" name="area" :value="old('area', $talhao->area)" placeholder="0,00" required />
            <flux:input.group.suffix>hectares</flux:input.group.suffix>
        </flux:input.group>
    </div>
</x-formulario.secao>

<x-formulario.secao titulo="Cultura e safra" icone="calendar-days" descricao="Cada atividade guarda a safra do talhão no dia do lançamento. Alterar a safra aqui não muda atividades já registradas.">
    <div class="grid items-start gap-6 sm:grid-cols-2">
        <flux:input name="cultura" label="Cultura" :value="old('cultura', $talhao->cultura)" placeholder="Ex.: Soja" list="culturas-sugeridas" required />
        <flux:input name="safra" label="Safra" :value="old('safra', $talhao->safra)" placeholder="Ex.: 2025/2026" required />
    </div>

    <datalist id="culturas-sugeridas">
        @foreach (['Soja', 'Milho', 'Amendoim', 'Cana-de-açúcar', 'Café', 'Laranja', 'Feijão', 'Trigo', 'Algodão', 'Sorgo'] as $cultura)
            <option value="{{ $cultura }}"></option>
        @endforeach
    </datalist>
</x-formulario.secao>

<x-formulario.secao titulo="Situação" icone="check-circle" descricao="Talhões inativos não contam na área ocupada da propriedade.">
    @include('partials.campo-situacao', ['opcoes' => \App\Enums\StatusTalhao::cases(), 'selecionada' => $talhao->status?->value])
</x-formulario.secao>

<x-formulario.acoes :cancelar="$talhao->exists ? route('talhoes.show', $talhao) : route('talhoes.index')" />
