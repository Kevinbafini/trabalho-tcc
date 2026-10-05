<x-formulario.secao titulo="Dados do funcionário" icone="users" descricao="Quem participa das atividades da propriedade.">
    @include('partials.campo-propriedade', ['registro' => $funcionario])

    <div class="grid items-start gap-6 sm:grid-cols-2">
        <flux:input name="nome" label="Nome" :value="old('nome', $funcionario->nome)" placeholder="Nome completo" required />
        <flux:input name="funcao" label="Função" :value="old('funcao', $funcionario->funcao)" placeholder="Ex.: Tratorista" list="funcoes-sugeridas" required />
    </div>

    <datalist id="funcoes-sugeridas">
        @foreach (['Operador de máquinas', 'Tratorista', 'Trabalhador rural', 'Encarregado de campo', 'Técnico agrícola'] as $funcao)
            <option value="{{ $funcao }}"></option>
        @endforeach
    </datalist>
</x-formulario.secao>

<x-formulario.secao titulo="Custo da mão de obra" icone="banknotes" descricao="Custo da mão de obra na atividade = horas trabalhadas × custo por hora. Atividades já registradas mantêm o valor do dia do lançamento.">
    <div class="sm:max-w-xs">
        <flux:input.group label="Custo por hora" name="custo_hora">
            <flux:input.group.prefix>R$</flux:input.group.prefix>
            <flux:input type="number" step="0.01" min="0" inputmode="decimal" name="custo_hora" :value="old('custo_hora', $funcionario->custo_hora)" placeholder="0,00" required />
            <flux:input.group.suffix>/ hora</flux:input.group.suffix>
        </flux:input.group>
    </div>
</x-formulario.secao>

<x-formulario.secao titulo="Situação" icone="check-circle" descricao="Funcionários inativos continuam no histórico das atividades.">
    @include('partials.campo-situacao', ['opcoes' => \App\Enums\StatusRecurso::cases(), 'selecionada' => $funcionario->status?->value])
</x-formulario.secao>

<x-formulario.acoes :cancelar="route('funcionarios.index')" />
