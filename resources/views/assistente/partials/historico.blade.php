{{-- Histórico de conversas da tela cheia (a selecionada fica destacada). --}}
@forelse ($conversas as $item)
    <a
        href="{{ route('assistente.index', ['conversa' => $item->id]) }}"
        wire:navigate
        @class([
            'block rounded-lg px-3 py-2.5 transition',
            'bg-zinc-100 dark:bg-white/10' => $conversaAtual?->id === $item->id,
            'hover:bg-zinc-50 dark:hover:bg-white/5' => $conversaAtual?->id !== $item->id,
        ])
        @if ($conversaAtual?->id === $item->id) aria-current="page" @endif
    >
        <span class="block truncate text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $item->titulo }}</span>
        <span class="mt-0.5 flex items-center gap-1.5 text-[11px] text-zinc-500 dark:text-zinc-400">
            <span>{{ $item->provedor->rotulo() }}</span>
            <span aria-hidden="true">·</span>
            <span>{{ $item->ultima_mensagem_em === null || abs($item->ultima_mensagem_em->diffInSeconds()) < 60 ? 'agora' : $item->ultima_mensagem_em->diffForHumans() }}</span>
        </span>
    </a>
@empty
    <div class="flex flex-col items-center gap-2 rounded-lg border border-dashed border-zinc-200 px-4 py-8 text-center dark:border-white/10">
        <flux:icon.chat-bubble-left-right class="text-zinc-400" />
        <flux:text size="sm">Suas conversas aparecerão aqui.</flux:text>
    </div>
@endforelse
