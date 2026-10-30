{{-- Título das telas de conta (login, cadastro, senha...). --}}
@props([
    'title',
    'description',
])

<div class="flex w-full flex-col">
    <h1 class="text-2xl font-semibold tracking-tight text-zinc-900 dark:text-white">{{ $title }}</h1>
    <p class="mt-1.5 text-sm text-zinc-500 dark:text-zinc-400">{{ $description }}</p>
</div>
