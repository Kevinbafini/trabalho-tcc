<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main>
        <x-mensagens />

        {{ $slot }}
    </flux:main>

    <x-assistente.gaveta />
</x-layouts::app.sidebar>
