{{--
    Campo "Propriedade" dos formulários de talhões e recursos.
    - Edição: apenas exibida (a propriedade não pode ser alterada).
    - Cadastro com uma propriedade: exibida e enviada em campo oculto.
    - Cadastro com várias propriedades: seleção.
--}}
@if ($registro->exists || $propriedades->count() === 1)
    @unless ($registro->exists)
        <input type="hidden" name="propriedade_id" value="{{ $propriedades->first()->id }}">
    @endunless

    <div class="flex items-center gap-3 rounded-lg border border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-700 dark:bg-white/5">
        <flux:icon.home-modern class="size-5 shrink-0 text-zinc-400" />

        <div class="min-w-0">
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Propriedade</p>
            <p class="truncate text-sm font-medium text-zinc-800 dark:text-white">
                {{ $registro->exists ? $registro->propriedade->nome : $propriedades->first()->nome }}
            </p>
        </div>

        @if ($registro->exists)
            <span class="ms-auto hidden text-xs text-zinc-500 sm:block dark:text-zinc-400">Não pode ser alterada</span>
        @endif
    </div>
@else
    <flux:select name="propriedade_id" label="Propriedade" placeholder="Selecione a propriedade">
        @foreach ($propriedades as $propriedade)
            <flux:select.option :value="$propriedade->id" :selected="(int) old('propriedade_id', $propriedadeSelecionada) === $propriedade->id">
                {{ $propriedade->nome }}
            </flux:select.option>
        @endforeach
    </flux:select>
@endif
