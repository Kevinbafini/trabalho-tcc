<div class="space-y-6">
    @include('partials.campo-propriedade', ['registro' => $implemento])

    <div class="grid gap-6 sm:grid-cols-2">
        <flux:input name="nome" label="Nome" :value="old('nome', $implemento->nome)" placeholder="Ex.: Grade aradora" required />
        <flux:input name="tipo" label="Tipo" :value="old('tipo', $implemento->tipo)" placeholder="Ex.: Preparo do solo" list="tipos-de-implemento" required />
    </div>

    <datalist id="tipos-de-implemento">
        @foreach (['Preparo do solo', 'Plantio', 'Pulverização', 'Adubação', 'Colheita', 'Transporte'] as $tipo)
            <option value="{{ $tipo }}"></option>
        @endforeach
    </datalist>

    <flux:input name="modelo" label="Modelo (opcional)" :value="old('modelo', $implemento->modelo)" />

    <flux:select name="status" label="Situação" description="Somente implementos ativos aparecem para novas atividades.">
        @foreach (\App\Enums\StatusEquipamento::cases() as $status)
            <flux:select.option :value="$status->value" :selected="old('status', $implemento->status?->value) === $status->value">{{ $status->rotulo() }}</flux:select.option>
        @endforeach
    </flux:select>

    @include('partials.botoes-formulario', ['cancelar' => route('implementos.index')])
</div>
