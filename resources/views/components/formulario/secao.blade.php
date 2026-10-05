{{--
    Seção de formulário: título e explicação à esquerda e os campos em um
    card à direita. Em telas menores que 1280px, o texto fica acima do card.
--}}
@props([
    'titulo',
    'descricao' => null,
    'icone' => null,
])

<section {{ $attributes->class('grid gap-x-10 gap-y-4 xl:grid-cols-3') }}>
    <div class="xl:pt-2">
        <div class="flex items-center gap-2">
            @if ($icone)
                <flux:icon :name="$icone" variant="mini" class="text-zinc-400 dark:text-zinc-500" />
            @endif
            <flux:heading size="lg">{{ $titulo }}</flux:heading>
        </div>

        @if ($descricao)
            <flux:text class="mt-1 text-sm">{{ $descricao }}</flux:text>
        @endif
    </div>

    <flux:card class="min-w-0 space-y-6 xl:col-span-2">
        {{ $slot }}
    </flux:card>
</section>
