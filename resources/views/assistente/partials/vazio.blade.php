{{-- Início de conversa no painel lateral: o que o assistente faz e sugestões de pedidos (um clique envia). --}}
<div class="mx-auto flex max-w-lg flex-col items-center py-6 text-center">
    <div class="flex size-11 items-center justify-center rounded-full border border-primary/15 bg-primary/8 text-primary dark:border-white/10 dark:bg-secondary/30 dark:text-accent-content">
        <flux:icon.sparkles />
    </div>

    <flux:heading size="lg" class="mt-4">Como posso ajudar?</flux:heading>
    <flux:text class="mt-1">
        Pergunte sobre os custos ou peça para cadastrar algo. Antes de salvar, eu mostro um resumo e só gravo com a sua confirmação.
    </flux:text>

    <div class="mt-6 grid w-full gap-2">
        @foreach (config('assistente.sugestoes') as $sugestao)
            <button
                type="button"
                x-on:click="usarSugestao(@js($sugestao['texto']))"
                class="flex cursor-pointer items-center gap-2.5 rounded-xl border border-zinc-200 bg-white px-3.5 py-2.5 text-start text-sm text-zinc-700 transition hover:bg-zinc-50 dark:border-white/10 dark:bg-white/5 dark:text-zinc-200 dark:hover:bg-white/10"
            >
                <flux:icon :name="$sugestao['icone']" variant="mini" class="shrink-0 text-zinc-400" />
                <span>{{ $sugestao['texto'] }}</span>
            </button>
        @endforeach
    </div>
</div>
