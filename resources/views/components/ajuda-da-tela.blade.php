{{--
    Botão "?" que abre uma janela explicando a tela: para que serve, o que é
    exibido e como usar. Os textos ficam em lang/pt_BR/ajuda.php, identificados
    pelo nome da rota da tela (ex.: "propriedades.index"). Sem texto cadastrado
    para a tela, o botão não aparece.
--}}
@props([
    'tela' => null,
    'icone' => null,
])

@php
    $tela ??= request()->route()?->getName();
    $ajuda = $tela ? trans('ajuda.'.$tela) : null;
    $negrito = fn (string $texto): string => Str::inlineMarkdown($texto, ['html_input' => 'escape']);
@endphp

@if (is_array($ajuda))
    <flux:modal.trigger name="ajuda-da-tela">
        <flux:tooltip content="O que é esta tela?">
            <button
                type="button"
                aria-label="Ajuda: o que é esta tela?"
                class="inline-flex size-7 shrink-0 cursor-pointer items-center justify-center rounded-full border border-zinc-300 bg-white text-sm font-semibold text-zinc-500 shadow-xs transition hover:border-zinc-400 hover:text-zinc-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-zinc-500 dark:border-white/15 dark:bg-white/5 dark:text-zinc-400 dark:hover:border-white/30 dark:hover:text-white"
            >?</button>
        </flux:tooltip>
    </flux:modal.trigger>

    <flux:modal name="ajuda-da-tela" class="w-full md:max-w-xl">
        <div class="space-y-6">
            <div class="flex items-center gap-3 pe-8">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-lg border border-zinc-200 bg-zinc-50 dark:border-white/10 dark:bg-white/5">
                    <flux:icon :name="$icone ?? 'question-mark-circle'" class="size-5 text-zinc-600 dark:text-zinc-300" />
                </div>

                <div>
                    <flux:text size="sm">Sobre esta tela</flux:text>
                    <flux:heading size="lg">{{ $ajuda['titulo'] }}</flux:heading>
                </div>
            </div>

            <section>
                <flux:heading class="flex items-center gap-2">
                    <flux:icon.light-bulb variant="mini" class="text-zinc-400" />
                    Para que serve
                </flux:heading>
                <flux:text class="mt-2">{!! $negrito($ajuda['proposito']) !!}</flux:text>
            </section>

            <section>
                <flux:heading class="flex items-center gap-2">
                    <flux:icon.eye variant="mini" class="text-zinc-400" />
                    O que é exibido
                </flux:heading>
                <ul class="mt-2 space-y-1.5">
                    @foreach ($ajuda['exibe'] as $item)
                        <li class="flex gap-2.5 text-sm text-zinc-600 dark:text-zinc-300">
                            <span class="mt-2 size-1.5 shrink-0 rounded-full bg-zinc-400 dark:bg-zinc-500" aria-hidden="true"></span>
                            <span>{!! $negrito($item) !!}</span>
                        </li>
                    @endforeach
                </ul>
            </section>

            <section>
                <flux:heading class="flex items-center gap-2">
                    <flux:icon.cursor-arrow-rays variant="mini" class="text-zinc-400" />
                    Como usar
                </flux:heading>
                <ol class="mt-2 space-y-2">
                    @foreach ($ajuda['como_usar'] as $passo)
                        <li class="flex gap-2.5 text-sm text-zinc-600 dark:text-zinc-300">
                            <span class="flex size-5 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-xs font-medium text-zinc-600 tabular-nums dark:bg-white/10 dark:text-zinc-300">{{ $loop->iteration }}</span>
                            <span>{!! $negrito($passo) !!}</span>
                        </li>
                    @endforeach
                </ol>
            </section>

            @isset($ajuda['dica'])
                <flux:callout icon="information-circle" variant="secondary">
                    <flux:callout.text>{!! $negrito($ajuda['dica']) !!}</flux:callout.text>
                </flux:callout>
            @endisset

            <div class="flex justify-end">
                <flux:modal.close>
                    <flux:button variant="primary">Entendi</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>
@endif
