{{--
    Layout das telas de conta (login, cadastro, senha, 2FA, autorização de apps de IA).
    Desktop: painel da marca à esquerda e formulário à direita. Celular: só o formulário, com o logo no topo.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-zinc-900">
        <div class="grid min-h-dvh lg:grid-cols-[minmax(0,1.05fr)_minmax(0,1fr)]">
            {{-- Painel da marca --}}
            <aside class="relative hidden overflow-hidden bg-linear-to-br from-primary via-primary-dark to-zinc-950 p-10 text-white lg:flex lg:flex-col xl:p-14">
                <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(white_1px,transparent_1.5px)] [background-size:24px_24px] opacity-[0.08] [mask-image:linear-gradient(to_bottom,black,transparent_85%)]" aria-hidden="true"></div>
                <div class="pointer-events-none absolute -top-28 -right-28 size-96 rounded-full bg-accent-green/25 blur-3xl" aria-hidden="true"></div>
                <div class="pointer-events-none absolute -bottom-36 -left-24 size-[28rem] rounded-full bg-success/20 blur-3xl" aria-hidden="true"></div>

                <a href="{{ route('home') }}" class="relative z-10 w-fit" wire:navigate>
                    <img src="{{ asset('images/logo-scaa-branca.png') }}" alt="SCAA — Sistema de Controle de Atividades Agrícolas" class="h-14 w-auto">
                </a>

                <div class="relative z-10 my-auto max-w-lg py-12">
                    <p class="text-xs font-semibold tracking-[0.18em] text-[#b9dca0] uppercase">Gestão de custos agrícolas</p>
                    <h2 class="mt-4 text-4xl leading-tight font-semibold tracking-tight text-balance xl:text-[2.75rem]">
                        Cada atividade da lavoura, com o custo certo em cada talhão.
                    </h2>
                    <p class="mt-4 text-base leading-7 text-white/70">
                        Registre as operações, acompanhe insumos, máquinas e mão de obra e veja o custo por hectare na hora.
                    </p>

                    <ul class="mt-10 space-y-6">
                        @foreach ([
                            ['map', 'Custo por talhão e por hectare', 'Insumos, maquinário e mão de obra apropriados em cada talhão.'],
                            ['clipboard-document-check', 'Histórico que não muda', 'Cada atividade guarda os preços do dia do lançamento.'],
                            ['sparkles', 'Assistente com IA', 'Pergunte sobre os custos ou peça cadastros em linguagem natural.'],
                        ] as [$icone, $titulo, $texto])
                            <li class="flex gap-4">
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-white/10 ring-1 ring-white/15" aria-hidden="true">
                                    <flux:icon :name="$icone" variant="mini" class="text-[#b9dca0]" />
                                </span>
                                <div>
                                    <p class="font-medium">{{ $titulo }}</p>
                                    <p class="mt-0.5 text-sm text-white/65">{{ $texto }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <p class="relative z-10 text-xs text-white/50">Trabalho de Conclusão de Curso · FATEC Bebedouro · Big Data no Agronegócio</p>
            </aside>

            {{-- Formulário --}}
            <main class="relative isolate flex flex-col">
                <div class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-56 bg-linear-to-b from-primary/6 to-transparent lg:hidden dark:from-secondary/20" aria-hidden="true"></div>

                <div class="flex flex-1 items-center justify-center px-6 py-10 sm:px-10">
                    <div class="w-full max-w-sm">
                        <a href="{{ route('home') }}" class="mb-10 inline-flex lg:hidden" wire:navigate>
                            <x-logo-scaa class="h-12" />
                        </a>

                        {{ $slot }}
                    </div>
                </div>

                <p class="px-6 pb-6 text-center text-xs text-zinc-400 lg:hidden">FATEC Bebedouro · Big Data no Agronegócio</p>
            </main>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
