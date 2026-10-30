{{-- Conteúdo do painel lateral do assistente (carregado ao abrir o painel, em qualquer tela). --}}
<div class="flex items-center gap-3 border-b border-zinc-200 py-3 ps-4 pe-12 dark:border-white/10">
    <div class="flex size-9 shrink-0 items-center justify-center rounded-full border border-zinc-200 bg-white text-zinc-700 dark:border-white/15 dark:bg-white/5 dark:text-zinc-200" aria-hidden="true">
        <flux:icon.sparkles variant="mini" />
    </div>

    <div class="min-w-0 flex-1">
        <flux:heading>Assistente IA</flux:heading>
        <flux:text size="sm" class="truncate">{{ $conversa?->titulo ?? 'Nova conversa' }}</flux:text>
    </div>

    <flux:button size="sm" variant="ghost" icon="plus" x-on:click="carregar({ nova: 1 })" tooltip="Nova conversa" aria-label="Nova conversa" />
    <flux:button
        size="sm"
        variant="ghost"
        icon="arrows-pointing-out"
        :href="route('assistente.index', array_filter(['conversa' => $conversa?->id]))"
        wire:navigate
        tooltip="Abrir em tela cheia"
        aria-label="Abrir em tela cheia"
    />
</div>

@include('assistente.partials.chat', ['variante' => 'painel'])
