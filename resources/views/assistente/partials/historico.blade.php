{{-- Histórico de conversas, agrupado por data (a conversa aberta fica destacada). --}}
@php
    $grupos = $conversas->groupBy(function (\App\Models\Conversa $item): string {
        $data = $item->ultima_mensagem_em ?? $item->created_at;

        return match (true) {
            $data === null, $data->isToday() => 'Hoje',
            $data->isYesterday() => 'Ontem',
            $data->greaterThan(now()->subDays(7)) => 'Últimos 7 dias',
            default => 'Mais antigas',
        };
    });
@endphp

@forelse ($grupos as $grupo => $itens)
    <div class="mb-4">
        <p class="px-3 pt-2 pb-1 text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ $grupo }}</p>

        @foreach ($itens as $item)
            <a
                href="{{ route('assistente.index', ['conversa' => $item->id]) }}"
                wire:navigate
                title="{{ $item->titulo }}"
                @class([
                    'block truncate rounded-lg px-3 py-2 text-sm transition',
                    'bg-primary/10 font-medium text-primary dark:bg-secondary/35 dark:text-white' => $conversaAtual?->id === $item->id,
                    'text-zinc-700 hover:bg-zinc-200/50 dark:text-zinc-300 dark:hover:bg-white/5' => $conversaAtual?->id !== $item->id,
                ])
                @if ($conversaAtual?->id === $item->id) aria-current="page" @endif
            >
                {{ $item->titulo }}
            </a>
        @endforeach
    </div>
@empty
    <p class="px-3 py-6 text-center text-sm text-zinc-500 dark:text-zinc-400">Suas conversas aparecerão aqui.</p>
@endforelse
