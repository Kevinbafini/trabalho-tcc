{{-- Início de conversa: o que o assistente faz e sugestões de pedidos (um clique envia). --}}
@php
    $sugestoes = [
        ['icone' => 'chart-pie', 'texto' => 'Faça um resumo dos meus custos nesta safra.'],
        ['icone' => 'map', 'texto' => 'Qual talhão tem o maior custo por hectare?'],
        ['icone' => 'clipboard-document-list', 'texto' => 'Quais foram as últimas atividades registradas?'],
        ['icone' => 'plus-circle', 'texto' => 'Quero registrar uma atividade agrícola.'],
    ];
@endphp

<div class="mx-auto flex max-w-lg flex-col items-center py-6 text-center">
    <div class="flex size-12 items-center justify-center rounded-xl bg-zinc-900 text-white shadow-sm dark:bg-white dark:text-zinc-900">
        <flux:icon.sparkles />
    </div>

    <flux:heading size="lg" class="mt-4">Como posso ajudar?</flux:heading>
    <flux:text class="mt-1">
        Pergunte sobre os custos ou peça para cadastrar algo. Antes de salvar, eu mostro um resumo e só gravo com a sua confirmação.
    </flux:text>

    <div class="mt-6 grid w-full gap-2 sm:grid-cols-2">
        @foreach ($sugestoes as $sugestao)
            <button
                type="button"
                x-on:click="usarSugestao(@js($sugestao['texto']))"
                class="flex cursor-pointer items-start gap-2.5 rounded-xl border border-zinc-200 bg-white px-3.5 py-3 text-start text-sm text-zinc-700 shadow-xs transition hover:border-zinc-300 hover:bg-zinc-50 dark:border-white/10 dark:bg-white/5 dark:text-zinc-200 dark:hover:bg-white/10"
            >
                <flux:icon :name="$sugestao['icone']" variant="mini" class="mt-0.5 shrink-0 text-zinc-400" />
                <span>{{ $sugestao['texto'] }}</span>
            </button>
        @endforeach
    </div>
</div>
