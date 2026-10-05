{{--
    Campo "Propriedade" dos formulários de talhões e recursos.
    No cadastro: seleção (ou fixa, se o usuário tiver só uma propriedade).
    Na edição: apenas exibida, pois a propriedade não pode ser alterada.
--}}
@if ($registro->exists)
    <flux:field>
        <flux:label>Propriedade</flux:label>
        <flux:text class="font-medium text-zinc-800 dark:text-white">{{ $registro->propriedade->nome }}</flux:text>
        <flux:description>A propriedade não pode ser alterada após o cadastro.</flux:description>
    </flux:field>
@elseif ($propriedades->count() === 1)
    <input type="hidden" name="propriedade_id" value="{{ $propriedades->first()->id }}">

    <flux:field>
        <flux:label>Propriedade</flux:label>
        <flux:text class="font-medium text-zinc-800 dark:text-white">{{ $propriedades->first()->nome }}</flux:text>
    </flux:field>
@else
    <flux:select name="propriedade_id" label="Propriedade" placeholder="Selecione a propriedade">
        @foreach ($propriedades as $propriedade)
            <flux:select.option :value="$propriedade->id" :selected="(int) old('propriedade_id', $propriedadeSelecionada) === $propriedade->id">
                {{ $propriedade->nome }}
            </flux:select.option>
        @endforeach
    </flux:select>
@endif
