@props([
    'sidebar' => false,
])

@if($sidebar)
    {{-- O nome do sistema é longo: quebra em duas linhas em vez de ser cortado. --}}
    <a {{ $attributes->class('flex min-w-0 items-center gap-2 px-2 py-1') }}>
        <span class="flex aspect-square size-8 shrink-0 items-center justify-center rounded-md bg-accent-content text-accent-foreground">
            <x-app-logo-icon class="size-5 text-white dark:text-black" />
        </span>
        <span class="text-sm leading-tight font-medium text-zinc-800 dark:text-zinc-100">{{ config('app.name') }}</span>
    </a>
@else
    <flux:brand :name="config('app.name')" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-md bg-accent-content text-accent-foreground">
            <x-app-logo-icon class="size-5 fill-current text-white dark:text-black" />
        </x-slot>
    </flux:brand>
@endif
