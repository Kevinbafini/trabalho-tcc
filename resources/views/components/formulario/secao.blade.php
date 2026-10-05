{{--
    Seção de formulário em um card: cabeçalho com ícone, título e explicação,
    e os campos logo abaixo.
--}}
@props([
    'titulo',
    'descricao' => null,
    'icone' => null,
])

<flux:card {{ $attributes->class('min-w-0 overflow-hidden p-0!') }}>
    <div class="flex items-start gap-3 border-b border-zinc-200 px-5 py-4 sm:px-6 dark:border-zinc-700">
        @if ($icone)
            <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-zinc-100 dark:bg-white/10">
                <flux:icon :name="$icone" variant="mini" class="text-zinc-500 dark:text-zinc-300" />
            </div>
        @endif

        <div class="min-w-0">
            <flux:heading>{{ $titulo }}</flux:heading>
            @if ($descricao)
                <flux:text class="mt-0.5 text-sm">{{ $descricao }}</flux:text>
            @endif
        </div>
    </div>

    <div class="space-y-6 p-5 sm:p-6">
        {{ $slot }}
    </div>
</flux:card>
