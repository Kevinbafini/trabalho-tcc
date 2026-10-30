<section class="w-full">
    @include('partials.settings-heading', ['ajuda' => 'dados-demonstracao.edit'])

    <flux:heading level="2" class="sr-only">Dados de demonstração</flux:heading>

    <x-settings.layout heading="Dados de demonstração" subheading="Preencha a sua conta com propriedades fictícias para explorar o sistema já populado.">
        @php($ativo = $this->resumo['propriedades'] > 0)

        <div class="space-y-6">
            {{-- Situação atual --}}
            <div @class([
                'flex items-start gap-4 rounded-xl border p-4',
                'border-primary/20 bg-primary/5 dark:border-secondary/40 dark:bg-secondary/15' => $ativo,
                'border-zinc-200 bg-white dark:border-white/10 dark:bg-white/5' => ! $ativo,
            ])>
                <span @class([
                    'flex size-10 shrink-0 items-center justify-center rounded-xl',
                    'bg-primary text-white dark:bg-secondary' => $ativo,
                    'bg-zinc-100 text-zinc-500 dark:bg-white/10 dark:text-zinc-300' => ! $ativo,
                ]) aria-hidden="true">
                    <flux:icon :name="$ativo ? 'check-circle' : 'circle-stack'" variant="mini" />
                </span>

                <div class="min-w-0 flex-1">
                    <p class="font-medium text-zinc-900 dark:text-white" data-test="situacao-demonstracao">
                        {{ $ativo ? 'Dados de demonstração ativos' : 'Dados de demonstração desativados' }}
                    </p>
                    <p class="mt-0.5 text-sm text-zinc-600 dark:text-zinc-300">
                        {{ $ativo
                            ? 'A sua conta tem as propriedades de exemplo. Elas aparecem junto com os seus cadastros, marcadas com “(demonstração)” no nome.'
                            : 'A sua conta mostra apenas os seus próprios cadastros.' }}
                    </p>
                </div>
            </div>

            @if ($ativo)
                <dl class="grid grid-cols-2 gap-3">
                    @foreach ([
                        ['Propriedades', Number::format($this->resumo['propriedades'])],
                        ['Talhões', Number::format($this->resumo['talhoes'])],
                        ['Atividades', Number::format($this->resumo['atividades'])],
                        ['Custo total', Number::currency($this->resumo['custo_total'])],
                    ] as [$rotulo, $valor])
                        <div class="rounded-xl border border-zinc-200 bg-white p-3 dark:border-white/10 dark:bg-white/5">
                            <dt class="text-xs text-zinc-500 dark:text-zinc-400">{{ $rotulo }}</dt>
                            <dd class="mt-1 font-semibold text-zinc-900 tabular-nums dark:text-white">{{ $valor }}</dd>
                        </div>
                    @endforeach
                </dl>

                <div>
                    <flux:modal.trigger name="remover-demonstracao">
                        <flux:button variant="danger" icon="trash">Remover dados de demonstração</flux:button>
                    </flux:modal.trigger>
                </div>
            @else
                <div>
                    <flux:heading size="sm">O que será criado</flux:heading>

                    <ul class="mt-3 space-y-3 text-sm text-zinc-600 dark:text-zinc-300">
                        @foreach ([
                            ['home-modern', 'Fazenda Santa Helena', '50 ha em Bebedouro/SP, com 5 talhões de soja, milho e amendoim.'],
                            ['sun', 'Sítio Boa Vista', '22 ha de pomar, com 3 quadras de laranja e limão.'],
                            ['users', 'Recursos de cada propriedade', 'Funcionários, máquinas, implementos e insumos com os seus custos.'],
                            ['clipboard-document-list', 'Uma safra inteira de atividades', 'Mais de 130 operações da safra 2025/2026, com os custos já calculados e um reajuste de preços em janeiro.'],
                        ] as [$icone, $titulo, $texto])
                            <li class="flex gap-3">
                                <flux:icon :name="$icone" variant="mini" class="mt-0.5 shrink-0 text-success dark:text-accent-content" />
                                <span><strong class="font-medium text-zinc-900 dark:text-white">{{ $titulo }}:</strong> {{ $texto }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <flux:button variant="primary" icon="sparkles" wire:click="ativar" data-test="ativar-demonstracao">
                    <span wire:loading.remove wire:target="ativar">Ativar dados de demonstração</span>
                    <span wire:loading wire:target="ativar">Criando os dados…</span>
                </flux:button>
            @endif

            <flux:text size="sm">
                Os seus próprios cadastros nunca são alterados: ativar cria propriedades novas, e remover apaga somente as propriedades de demonstração.
            </flux:text>
        </div>

        <flux:modal name="remover-demonstracao" class="max-w-md">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Remover dados de demonstração?</flux:heading>
                    <flux:text class="mt-2">
                        As propriedades de demonstração serão excluídas com todos os talhões, recursos e atividades, inclusive o que você alterou ou cadastrou nelas. As suas outras propriedades continuam como estão.
                    </flux:text>
                </div>

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancelar</flux:button>
                    </flux:modal.close>

                    <flux:button variant="danger" wire:click="remover" data-test="remover-demonstracao">Remover</flux:button>
                </div>
            </div>
        </flux:modal>
    </x-settings.layout>
</section>
