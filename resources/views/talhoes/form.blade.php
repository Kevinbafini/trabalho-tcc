<div class="space-y-6">
    @include('partials.campo-propriedade', ['registro' => $talhao])

    <div class="grid gap-6 sm:grid-cols-2">
        <flux:input name="nome" label="Nome do talhão" :value="old('nome', $talhao->nome)" placeholder="Ex.: Talhão 01" required />

        <flux:input
            type="number"
            step="0.01"
            min="0"
            inputmode="decimal"
            name="area"
            label="Área (hectares)"
            :value="old('area', $talhao->area)"
            required
        />
    </div>

    <div class="grid gap-6 sm:grid-cols-2">
        <flux:input name="cultura" label="Cultura" :value="old('cultura', $talhao->cultura)" placeholder="Ex.: Soja" list="culturas-sugeridas" required />

        <flux:input
            name="safra"
            label="Safra"
            :value="old('safra', $talhao->safra)"
            placeholder="Ex.: 2025/2026"
            description="Formato ano/ano. As atividades guardam a safra do talhão no dia do lançamento."
            required
        />
    </div>

    <datalist id="culturas-sugeridas">
        @foreach (['Soja', 'Milho', 'Amendoim', 'Cana-de-açúcar', 'Café', 'Laranja', 'Feijão', 'Trigo', 'Algodão', 'Sorgo'] as $cultura)
            <option value="{{ $cultura }}"></option>
        @endforeach
    </datalist>

    <flux:select name="status" label="Situação" description="Talhões inativos não ocupam área da propriedade e não aparecem para novas atividades.">
        @foreach (\App\Enums\StatusTalhao::cases() as $status)
            <flux:select.option :value="$status->value" :selected="old('status', $talhao->status?->value) === $status->value">{{ $status->rotulo() }}</flux:select.option>
        @endforeach
    </flux:select>

    @include('partials.botoes-formulario', ['cancelar' => $talhao->exists ? route('talhoes.show', $talhao) : route('talhoes.index')])
</div>
