{{-- Filtro por propriedade nas listagens. Só aparece quando o usuário tem mais de uma propriedade. --}}
@props([
    'propriedades',
    'selecionada' => null,
])

@if ($propriedades->count() > 1)
    <form method="GET" class="mb-4 max-w-xs">
        <flux:select name="propriedade_id" label="Propriedade" onchange="this.form.submit()">
            <flux:select.option value="">Todas as propriedades</flux:select.option>
            @foreach ($propriedades as $propriedade)
                <flux:select.option :value="$propriedade->id" :selected="$selecionada === $propriedade->id">
                    {{ $propriedade->nome }}
                </flux:select.option>
            @endforeach
        </flux:select>
    </form>
@endif
