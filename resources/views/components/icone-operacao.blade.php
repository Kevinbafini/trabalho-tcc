{{-- Ícone colorido de cada tipo de operação agrícola (atividades). --}}
@props([
    'tipo',
    'tamanho' => 'md',
])

@php
    [$icone, $cores] = match ($tipo) {
        \App\Enums\TipoOperacao::PreparoSolo => ['square-3-stack-3d', 'bg-stone-500/15 text-stone-600 dark:text-stone-300'],
        \App\Enums\TipoOperacao::Plantio => ['sparkles', 'bg-green-500/15 text-green-700 dark:text-green-400'],
        \App\Enums\TipoOperacao::Adubacao => ['beaker', 'bg-amber-500/15 text-amber-700 dark:text-amber-400'],
        \App\Enums\TipoOperacao::Pulverizacao => ['cloud', 'bg-violet-500/15 text-violet-700 dark:text-violet-300'],
        \App\Enums\TipoOperacao::Irrigacao => ['cloud-arrow-down', 'bg-sky-500/15 text-sky-700 dark:text-sky-400'],
        \App\Enums\TipoOperacao::TratosCulturais => ['scissors', 'bg-lime-500/15 text-lime-700 dark:text-lime-400'],
        \App\Enums\TipoOperacao::Colheita => ['truck', 'bg-orange-500/15 text-orange-700 dark:text-orange-400'],
        \App\Enums\TipoOperacao::Outra => ['ellipsis-horizontal-circle', 'bg-zinc-500/15 text-zinc-600 dark:text-zinc-300'],
    };
@endphp

<span
    {{ $attributes->class(['flex shrink-0 items-center justify-center', $cores, $tamanho === 'sm' ? 'size-7 rounded-md' : 'size-10 rounded-lg']) }}
    title="{{ $tipo->rotulo() }}"
    aria-hidden="true"
>
    <flux:icon :name="$icone" :variant="$tamanho === 'sm' ? 'micro' : 'mini'" />
</span>
