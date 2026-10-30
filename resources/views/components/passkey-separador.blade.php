{{-- Divisor "ou" entre o login por chave de acesso e o login por e-mail. --}}
@props([
    'texto',
])

<div class="relative my-6">
    <div class="absolute inset-0 flex items-center" aria-hidden="true">
        <div class="w-full border-t border-zinc-200 dark:border-zinc-700"></div>
    </div>
    <div class="relative flex justify-center text-xs uppercase">
        <span class="bg-white px-2 text-zinc-500 dark:bg-zinc-900 dark:text-zinc-400">{{ $texto }}</span>
    </div>
</div>
