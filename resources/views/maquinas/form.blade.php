<x-formulario.secao titulo="Identificação" icone="truck" descricao="Tratores, colhedoras, pulverizadores e demais máquinas da propriedade.">
    @include('partials.campo-propriedade', ['registro' => $maquina])

    <div class="grid items-start gap-6 sm:grid-cols-2">
        <flux:input name="nome" label="Nome" :value="old('nome', $maquina->nome)" placeholder="Ex.: Trator John Deere 6110J" required />
        <flux:input name="tipo" label="Tipo" :value="old('tipo', $maquina->tipo)" placeholder="Ex.: Trator" list="tipos-de-maquina" required />
    </div>

    <div class="grid items-start gap-6 sm:grid-cols-2">
        <flux:input name="modelo" label="Modelo (opcional)" :value="old('modelo', $maquina->modelo)" placeholder="Ex.: 6110J" />
        <flux:input type="number" min="1950" step="1" name="ano" label="Ano (opcional)" :value="old('ano', $maquina->ano)" placeholder="Ex.: 2020" />
    </div>
</x-formulario.secao>

<x-formulario.secao titulo="Custo de uso" icone="banknotes" descricao="Custo da máquina na atividade = horas de uso × custo por hora. Inclua o custo operacional dos implementos que ela traciona.">
    <div class="grid items-start gap-6 sm:grid-cols-2">
        <flux:input.group label="Custo por hora" name="custo_hora" description:trailing="Atividades já registradas mantêm o valor do dia do lançamento.">
            <flux:input.group.prefix>R$</flux:input.group.prefix>
            <flux:input type="number" step="0.01" min="0" inputmode="decimal" name="custo_hora" :value="old('custo_hora', $maquina->custo_hora)" placeholder="0,00" required />
            <flux:input.group.suffix>/ hora</flux:input.group.suffix>
        </flux:input.group>
    </div>
</x-formulario.secao>

<x-formulario.secao titulo="Situação" icone="check-circle" descricao="Somente máquinas ativas aparecem para novas atividades.">
    @include('partials.campo-situacao', ['opcoes' => \App\Enums\StatusEquipamento::cases(), 'selecionada' => $maquina->status?->value])
</x-formulario.secao>

{{-- Sugestões de preenchimento (listas usadas pelos campos com "list"). --}}
<datalist id="tipos-de-maquina">
    @foreach (['Trator', 'Colhedora', 'Pulverizador autopropelido', 'Caminhão', 'Plantadeira autopropelida'] as $tipo)
        <option value="{{ $tipo }}"></option>
    @endforeach
</datalist>

<x-formulario.acoes :cancelar="route('maquinas.index')" />
