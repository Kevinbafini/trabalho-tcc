{{-- Mensagem exibida quando ainda não há registros, com a ação para começar. --}}
@props([
    'titulo',
    'descricao' => null,
    'icone' => 'inbox',
])

<div {{ $attributes->class('flex flex-col items-center rounded-xl border border-dashed border-zinc-300 px-6 py-12 text-center dark:border-zinc-600') }}>
    <flux:icon :name="$icone" class="size-8 text-zinc-400" />
    <flux:heading size="lg" class="mt-3">{{ $titulo }}</flux:heading>

    @if ($descricao)
        <flux:text class="mt-1 max-w-md">{{ $descricao }}</flux:text>
    @endif

    @if ($slot->isNotEmpty())
        <div class="mt-5">
            {{ $slot }}
        </div>
    @endif
</div>
