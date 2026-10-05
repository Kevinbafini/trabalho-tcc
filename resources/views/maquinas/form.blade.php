<div class="space-y-6">
    @include('partials.campo-propriedade', ['registro' => $maquina])

    <div class="grid gap-6 sm:grid-cols-2">
        <flux:input name="nome" label="Nome" :value="old('nome', $maquina->nome)" placeholder="Ex.: Trator 01" required />
        <flux:input name="tipo" label="Tipo" :value="old('tipo', $maquina->tipo)" placeholder="Ex.: Trator" list="tipos-de-maquina" required />
    </div>

    <datalist id="tipos-de-maquina">
        @foreach (['Trator', 'Colhedora', 'Pulverizador autopropelido', 'Caminhão', 'Plantadeira autopropelida'] as $tipo)
            <option value="{{ $tipo }}"></option>
        @endforeach
    </datalist>

    <div class="grid gap-6 sm:grid-cols-3">
        <flux:input name="modelo" label="Modelo (opcional)" :value="old('modelo', $maquina->modelo)" />
        <flux:input type="number" min="1950" step="1" name="ano" label="Ano (opcional)" :value="old('ano', $maquina->ano)" />
        <flux:input
            type="number"
            step="0.01"
            min="0"
            inputmode="decimal"
            name="custo_hora"
            label="Custo por hora (R$)"
            :value="old('custo_hora', $maquina->custo_hora)"
            required
        />
    </div>

    <flux:text class="text-sm">O custo por hora deve incluir o custo operacional dos implementos tracionados pela máquina. Atividades já registradas mantêm o valor do dia do lançamento.</flux:text>

    <flux:select name="status" label="Situação" description="Somente máquinas ativas aparecem para novas atividades.">
        @foreach (\App\Enums\StatusEquipamento::cases() as $status)
            <flux:select.option :value="$status->value" :selected="old('status', $maquina->status?->value) === $status->value">{{ $status->rotulo() }}</flux:select.option>
        @endforeach
    </flux:select>

    @include('partials.botoes-formulario', ['cancelar' => route('maquinas.index')])
</div>
