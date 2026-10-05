<div class="space-y-6">
    @include('partials.campo-propriedade', ['registro' => $funcionario])

    <flux:input name="nome" label="Nome" :value="old('nome', $funcionario->nome)" required />

    <div class="grid gap-6 sm:grid-cols-2">
        <flux:input name="funcao" label="Função" :value="old('funcao', $funcionario->funcao)" placeholder="Ex.: Operador de máquinas" required />

        <flux:input
            type="number"
            step="0.01"
            min="0"
            inputmode="decimal"
            name="custo_hora"
            label="Custo por hora (R$)"
            description="Atividades já registradas mantêm o valor do dia do lançamento."
            :value="old('custo_hora', $funcionario->custo_hora)"
            required
        />
    </div>

    <flux:select name="status" label="Situação" description="Funcionários inativos não aparecem para novas atividades.">
        @foreach (\App\Enums\StatusRecurso::cases() as $status)
            <flux:select.option :value="$status->value" :selected="old('status', $funcionario->status?->value) === $status->value">{{ $status->rotulo() }}</flux:select.option>
        @endforeach
    </flux:select>

    @include('partials.botoes-formulario', ['cancelar' => route('funcionarios.index')])
</div>
