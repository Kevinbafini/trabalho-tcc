<x-formulario.secao titulo="Identificação" icone="wrench-screwdriver" descricao="Grades, plantadeiras, pulverizadores de barras e outros implementos.">
    @include('partials.campo-propriedade', ['registro' => $implemento])

    <div class="grid items-start gap-6 sm:grid-cols-2">
        <flux:input name="nome" label="Nome" :value="old('nome', $implemento->nome)" placeholder="Ex.: Grade aradora" required />
        <flux:input name="tipo" label="Tipo" :value="old('tipo', $implemento->tipo)" placeholder="Ex.: Preparo do solo" list="tipos-de-implemento" required />
    </div>

    <div class="grid items-start gap-6 sm:grid-cols-2">
        <flux:input name="modelo" label="Modelo (opcional)" :value="old('modelo', $implemento->modelo)" />
    </div>

    <flux:callout icon="information-circle">
        <flux:callout.text>Implementos não têm custo próprio: o custo operacional deles está incluído no custo por hora da máquina que os traciona.</flux:callout.text>
    </flux:callout>
</x-formulario.secao>

<x-formulario.secao titulo="Situação" icone="check-circle" descricao="Somente implementos ativos aparecem para novas atividades.">
    @include('partials.campo-situacao', ['opcoes' => \App\Enums\StatusEquipamento::cases(), 'selecionada' => $implemento->status?->value])
</x-formulario.secao>

{{-- Sugestões de preenchimento (listas usadas pelos campos com "list"). --}}
<datalist id="tipos-de-implemento">
    @foreach (['Preparo do solo', 'Plantio', 'Pulverização', 'Adubação', 'Colheita', 'Transporte'] as $tipo)
        <option value="{{ $tipo }}"></option>
    @endforeach
</datalist>

<x-formulario.acoes :cancelar="route('implementos.index')" />
