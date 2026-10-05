{{--
    Campo "Situação" em cards de opção, com uma explicação em cada opção.
    Variáveis: $opcoes (casos do enum de status) e $selecionada (valor atual).
--}}
<div>
    <flux:radio.group name="status" variant="cards" class="flex-col sm:flex-row">
        @foreach ($opcoes as $opcao)
            <flux:radio
                :value="$opcao->value"
                :label="$opcao->rotulo()"
                :description="$opcao->descricao()"
                :checked="old('status', $selecionada) === $opcao->value"
                class="flex-1"
            />
        @endforeach
    </flux:radio.group>

    <flux:error name="status" />
</div>
