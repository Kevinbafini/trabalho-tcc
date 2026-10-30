{{--
    Página pública de apresentação do SCAA (rota "home", em /).
    Explica o problema, os recursos, como funciona, o cálculo dos custos e o
    assistente com IA. Os exemplos de valores são ilustrativos.
--}}
@php
    $recursos = [
        ['home-modern', 'Propriedades e talhões', 'Cadastre as propriedades e divida cada uma em talhões com área, cultura e safra. A soma das áreas é conferida automaticamente.'],
        ['users', 'Recursos da produção', 'Funcionários e máquinas com custo por hora, implementos e insumos com valor unitário e estoque.'],
        ['clipboard-document-list', 'Atividades agrícolas', 'Plantio, adubação, pulverização, colheita e outras operações, com os recursos usados em cada uma.'],
        ['banknotes', 'Custo por talhão e por hectare', 'Insumos, maquinário e mão de obra apropriados automaticamente em cada talhão, com custo por hectare e por cultura.'],
        ['document-chart-bar', 'Relatórios e gráficos', 'Filtros por propriedade, talhão, safra e período, gráficos da evolução dos custos e relatório em PDF.'],
        ['sparkles', 'Assistente com IA', 'Consulte os custos e faça cadastros conversando, com Claude ou OpenAI, sempre com prévia e confirmação.'],
    ];

    $diferenciais = [
        ['clock', 'Histórico preservado'],
        ['shield-check', 'Dados separados por usuário'],
        ['device-phone-mobile', 'Funciona no celular'],
        ['moon', 'Modo claro e escuro'],
    ];

    $passos = [
        ['Cadastre a estrutura', 'Propriedade, talhões e os recursos da produção, cada um com o seu custo.'],
        ['Registre as atividades', 'Informe o talhão, a operação, os insumos e as horas de máquina e de trabalho.'],
        ['Acompanhe os custos', 'Veja quanto custou cada talhão e cada hectare, compare culturas e gere relatórios.'],
    ];

    $perguntas = [
        ['Preciso instalar alguma coisa?', 'Não. O sistema funciona no navegador do computador, do tablet ou do celular. Basta criar uma conta e entrar.'],
        ['Como o custo de uma atividade é calculado?', 'Insumos: quantidade × valor unitário. Máquinas e mão de obra: horas × custo por hora. Os implementos não têm custo próprio, porque já estão incluídos no custo por hora da máquina. O custo por hectare é o custo total do talhão dividido pela área dele.'],
        ['Se o preço de um insumo mudar, as atividades antigas mudam?', 'Não. Cada atividade guarda os valores do dia do lançamento. Um novo preço vale apenas para as próximas atividades, e o histórico das safras passadas continua correto.'],
        ['Outras pessoas veem os meus dados?', 'Não. Cada usuário enxerga somente as próprias propriedades, cadastros e atividades, inclusive quando usa o assistente de IA.'],
        ['O assistente de IA pode alterar algo sem eu saber?', 'Não. Antes de qualquer cadastro, ele mostra uma prévia com os dados e os custos calculados e só grava depois da sua confirmação. Ele também não edita nem exclui registros.'],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        @include('partials.head', ['title' => 'Controle de custos agrícolas por talhão'])
        <meta name="description" content="O SCAA registra as atividades agrícolas e apropria os custos de insumos, máquinas e mão de obra por propriedade e por talhão, com custo por hectare, relatórios e assistente com IA.">
    </head>
    <body class="min-h-screen bg-white text-zinc-800 antialiased dark:bg-zinc-900 dark:text-zinc-100">
        {{-- Topo --}}
        <header x-data="{ menuAberto: false }" class="sticky top-0 z-40 border-b border-zinc-200/70 bg-white/80 backdrop-blur-lg dark:border-white/10 dark:bg-zinc-900/80">
            <div class="mx-auto flex h-16 max-w-7xl items-center gap-6 px-6">
                <a href="{{ route('home') }}" class="shrink-0" aria-label="SCAA — página inicial">
                    <x-logo-scaa class="h-10" />
                </a>

                <nav class="hidden flex-1 items-center justify-center gap-8 text-sm font-medium text-zinc-600 md:flex dark:text-zinc-300" aria-label="Seções da página">
                    <a href="#recursos" class="transition hover:text-primary dark:hover:text-white">Recursos</a>
                    <a href="#como-funciona" class="transition hover:text-primary dark:hover:text-white">Como funciona</a>
                    <a href="#assistente" class="transition hover:text-primary dark:hover:text-white">Assistente IA</a>
                    <a href="#perguntas" class="transition hover:text-primary dark:hover:text-white">Perguntas</a>
                </nav>

                <div class="ms-auto hidden items-center gap-2 md:flex">
                    @auth
                        <flux:button :href="route('dashboard')" variant="primary" icon:trailing="arrow-right">Ir para o Dashboard</flux:button>
                    @else
                        <flux:button :href="route('login')" variant="ghost">Entrar</flux:button>
                        <flux:button :href="route('register')" variant="primary">Criar minha conta</flux:button>
                    @endauth
                </div>

                <button
                    type="button"
                    class="ms-auto inline-flex size-10 cursor-pointer items-center justify-center rounded-lg text-zinc-600 hover:bg-zinc-100 md:hidden dark:text-zinc-300 dark:hover:bg-white/10"
                    x-on:click="menuAberto = ! menuAberto"
                    x-bind:aria-expanded="menuAberto"
                    aria-controls="menu-movel"
                    aria-label="Abrir o menu"
                >
                    <flux:icon.bars-3 x-show="! menuAberto" />
                    <flux:icon.x-mark x-show="menuAberto" x-cloak />
                </button>
            </div>

            <div id="menu-movel" x-show="menuAberto" x-cloak x-transition.opacity class="border-t border-zinc-200 px-6 pb-6 md:hidden dark:border-white/10">
                <nav class="flex flex-col py-3 text-base font-medium" aria-label="Seções da página">
                    @foreach (['recursos' => 'Recursos', 'como-funciona' => 'Como funciona', 'assistente' => 'Assistente IA', 'perguntas' => 'Perguntas'] as $ancora => $rotulo)
                        <a href="#{{ $ancora }}" x-on:click="menuAberto = false" class="rounded-lg px-2 py-2.5 text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-white/10">{{ $rotulo }}</a>
                    @endforeach
                </nav>

                <div class="grid gap-2">
                    @auth
                        <flux:button :href="route('dashboard')" variant="primary" class="w-full">Ir para o Dashboard</flux:button>
                    @else
                        <flux:button :href="route('register')" variant="primary" class="w-full">Criar minha conta</flux:button>
                        <flux:button :href="route('login')" class="w-full">Entrar</flux:button>
                    @endauth
                </div>
            </div>
        </header>

        <main>
            {{-- Abertura --}}
            <section class="relative isolate overflow-hidden">
                <div class="pointer-events-none absolute inset-0 -z-10" aria-hidden="true">
                    <div class="absolute inset-0 bg-[radial-gradient(var(--color-primary)_1px,transparent_1.5px)] [background-size:24px_24px] opacity-[0.12] [mask-image:radial-gradient(ellipse_at_top,black,transparent_70%)] dark:bg-[radial-gradient(white_1px,transparent_1.5px)] dark:opacity-[0.06]"></div>
                    <div class="absolute -top-40 left-1/2 h-[36rem] w-[60rem] -translate-x-1/2 rounded-full bg-accent-green/15 blur-3xl dark:bg-secondary/25"></div>
                </div>

                <div class="mx-auto max-w-7xl px-6 pt-16 pb-20 sm:pt-24">
                    <div class="mx-auto max-w-3xl text-center">
                        <span class="inline-flex items-center gap-2 rounded-full border border-primary/15 bg-white/70 px-3.5 py-1.5 text-xs font-medium text-primary shadow-xs dark:border-white/10 dark:bg-white/5 dark:text-accent-content">
                            <span class="size-1.5 rounded-full bg-success" aria-hidden="true"></span>
                            Gestão de custos agrícolas por talhão
                        </span>

                        <h1 class="mt-6 text-4xl leading-[1.1] font-semibold tracking-tight text-balance text-zinc-900 sm:text-6xl dark:text-white">
                            Saiba quanto custa
                            <span class="bg-linear-to-r from-primary via-secondary to-accent-green bg-clip-text text-transparent dark:from-[#b9dca0] dark:via-accent-green dark:to-[#d4e5cb]">cada talhão</span>
                            da sua lavoura.
                        </h1>

                        <p class="mx-auto mt-6 max-w-2xl text-lg leading-8 text-zinc-600 dark:text-zinc-300">
                            O SCAA registra as atividades agrícolas e apropria automaticamente os custos de insumos, máquinas e mão de obra por propriedade e por talhão, com custo por hectare, relatórios em PDF e um assistente com inteligência artificial.
                        </p>

                        <div class="mt-10 flex flex-col items-center justify-center gap-3 sm:flex-row">
                            @auth
                                <flux:button :href="route('dashboard')" variant="primary" icon:trailing="arrow-right" class="h-12! px-6! text-base!">Ir para o Dashboard</flux:button>
                            @else
                                <flux:button :href="route('register')" variant="primary" icon:trailing="arrow-right" class="h-12! px-6! text-base!">Criar minha conta</flux:button>
                            @endauth
                            <flux:button href="#como-funciona" class="h-12! px-6! text-base!">Ver como funciona</flux:button>
                        </div>

                        <p class="mt-5 text-sm text-zinc-500 dark:text-zinc-400">Funciona no navegador, no computador e no celular.</p>
                    </div>

                    {{-- Prévia do sistema --}}
                    <div class="relative mx-auto mt-16 max-w-6xl">
                        <div class="pointer-events-none absolute -inset-x-10 -top-10 -bottom-20 -z-10 rounded-[3rem] bg-linear-to-b from-primary/15 via-accent-green/10 to-transparent blur-2xl dark:from-secondary/30" aria-hidden="true"></div>

                        <div class="rounded-2xl border border-zinc-200 bg-white/70 p-2 shadow-2xl shadow-primary/15 ring-1 ring-zinc-900/5 backdrop-blur dark:border-white/10 dark:bg-white/5 dark:shadow-black/40">
                            <div class="flex items-center gap-1.5 px-3 pt-1 pb-2.5" aria-hidden="true">
                                <span class="size-2.5 rounded-full bg-zinc-300 dark:bg-white/20"></span>
                                <span class="size-2.5 rounded-full bg-zinc-300 dark:bg-white/20"></span>
                                <span class="size-2.5 rounded-full bg-zinc-300 dark:bg-white/20"></span>
                            </div>
                            <img src="{{ asset('images/previa-dashboard-clara.webp') }}" width="1440" height="900" class="w-full rounded-xl border border-zinc-200 dark:hidden" alt="Dashboard do SCAA com indicadores de área, talhões, atividades e custos, a composição dos custos e o custo por hectare de cada talhão">
                            <img src="{{ asset('images/previa-dashboard-escura.webp') }}" width="1440" height="900" class="hidden w-full rounded-xl border border-white/10 dark:block" alt="Dashboard do SCAA no modo escuro" loading="lazy">
                        </div>
                    </div>
                </div>
            </section>

            {{-- O problema --}}
            <section class="border-y border-zinc-200 bg-fundo py-20 dark:border-white/10 dark:bg-zinc-950/40">
                <div class="mx-auto max-w-7xl px-6">
                    <div class="mx-auto max-w-2xl text-center">
                        <p class="text-sm font-semibold tracking-wide text-secondary uppercase dark:text-accent-content">Por que controlar por talhão</p>
                        <h2 class="mt-3 text-3xl font-semibold tracking-tight text-balance text-zinc-900 sm:text-4xl dark:text-white">Sem controle, o custo da lavoura se perde no caminho.</h2>
                    </div>

                    <div class="mt-14 grid gap-6 md:grid-cols-3">
                        @foreach ([
                            ['archive-box', 'Custos espalhados', 'Notas, cadernos e planilhas soltas dificultam saber quanto foi gasto de verdade em cada operação.'],
                            ['chart-bar-square', 'Média que esconde problemas', 'O custo da fazenda inteira não mostra qual talhão consome mais e onde vale a pena agir.'],
                            ['exclamation-circle', 'Preços que mudam', 'Sem histórico, um reajuste no preço do insumo distorce o custo das safras que já passaram.'],
                        ] as [$icone, $titulo, $texto])
                            <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-white/10 dark:bg-white/5">
                                <span class="flex size-11 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400" aria-hidden="true">
                                    <flux:icon :name="$icone" />
                                </span>
                                <h3 class="mt-5 text-lg font-semibold text-zinc-900 dark:text-white">{{ $titulo }}</h3>
                                <p class="mt-2 text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ $texto }}</p>
                            </div>
                        @endforeach
                    </div>

                    <p class="mx-auto mt-12 max-w-2xl text-center text-lg leading-8 text-zinc-700 dark:text-zinc-200">
                        O SCAA organiza tudo em um só lugar: cada atividade é registrada no talhão onde aconteceu, e o custo é calculado pelo sistema, item por item.
                    </p>
                </div>
            </section>

            {{-- Recursos --}}
            <section id="recursos" class="scroll-mt-20 py-24">
                <div class="mx-auto max-w-7xl px-6">
                    <div class="mx-auto max-w-2xl text-center">
                        <p class="text-sm font-semibold tracking-wide text-secondary uppercase dark:text-accent-content">Recursos</p>
                        <h2 class="mt-3 text-3xl font-semibold tracking-tight text-balance text-zinc-900 sm:text-4xl dark:text-white">Tudo o que você precisa para apropriar os custos da produção.</h2>
                    </div>

                    <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($recursos as [$icone, $titulo, $texto])
                            <div class="group relative overflow-hidden rounded-2xl border border-zinc-200 bg-white p-6 transition duration-200 hover:-translate-y-0.5 hover:border-primary/25 hover:shadow-lg hover:shadow-primary/5 dark:border-white/10 dark:bg-white/5 dark:hover:border-secondary/60">
                                <span class="absolute inset-x-0 top-0 h-0.5 bg-linear-to-r from-primary via-secondary to-accent-green opacity-0 transition group-hover:opacity-100" aria-hidden="true"></span>
                                <span class="flex size-11 items-center justify-center rounded-xl bg-primary/8 text-primary dark:bg-secondary/30 dark:text-accent-content" aria-hidden="true">
                                    <flux:icon :name="$icone" />
                                </span>
                                <h3 class="mt-5 text-lg font-semibold text-zinc-900 dark:text-white">{{ $titulo }}</h3>
                                <p class="mt-2 text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ $texto }}</p>
                            </div>
                        @endforeach
                    </div>

                    <ul class="mt-10 flex flex-wrap justify-center gap-3">
                        @foreach ($diferenciais as [$icone, $texto])
                            <li class="inline-flex items-center gap-2 rounded-full border border-zinc-200 bg-white px-4 py-2 text-sm text-zinc-700 dark:border-white/10 dark:bg-white/5 dark:text-zinc-200">
                                <flux:icon :name="$icone" variant="mini" class="text-success dark:text-accent-content" />
                                {{ $texto }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            </section>

            {{-- Como funciona --}}
            <section id="como-funciona" class="scroll-mt-20 border-y border-zinc-200 bg-fundo py-24 dark:border-white/10 dark:bg-zinc-950/40">
                <div class="mx-auto max-w-7xl px-6">
                    <div class="mx-auto max-w-2xl text-center">
                        <p class="text-sm font-semibold tracking-wide text-secondary uppercase dark:text-accent-content">Como funciona</p>
                        <h2 class="mt-3 text-3xl font-semibold tracking-tight text-balance text-zinc-900 sm:text-4xl dark:text-white">Do cadastro ao custo por hectare em três passos.</h2>
                    </div>

                    <div class="relative mt-14">
                    <div class="absolute top-6 right-[16%] left-[16%] hidden h-px bg-linear-to-r from-primary/0 via-primary/30 to-primary/0 md:block dark:via-white/20" aria-hidden="true"></div>

                    <ol class="relative grid gap-6 md:grid-cols-3">

                        @foreach ($passos as $indice => [$titulo, $texto])
                            <li class="relative text-center">
                                <span class="relative mx-auto flex size-12 items-center justify-center rounded-full bg-primary text-lg font-semibold text-white shadow-lg ring-8 shadow-primary/20 ring-fundo dark:bg-secondary dark:ring-zinc-900">
                                    {{ $indice + 1 }}
                                </span>
                                <h3 class="mt-6 text-lg font-semibold text-zinc-900 dark:text-white">{{ $titulo }}</h3>
                                <p class="mx-auto mt-2 max-w-xs text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ $texto }}</p>
                            </li>
                        @endforeach
                    </ol>
                    </div>
                </div>
            </section>

            {{-- Transparência do cálculo --}}
            <section id="calculo" class="scroll-mt-20 py-24">
                <div class="mx-auto grid max-w-7xl items-center gap-14 px-6 lg:grid-cols-2">
                    <div>
                        <p class="text-sm font-semibold tracking-wide text-secondary uppercase dark:text-accent-content">Cálculo transparente</p>
                        <h2 class="mt-3 text-3xl font-semibold tracking-tight text-balance text-zinc-900 sm:text-4xl dark:text-white">Cada real do custo, explicado item por item.</h2>
                        <p class="mt-5 text-lg leading-8 text-zinc-600 dark:text-zinc-300">
                            Nada de caixa-preta: o sistema mostra de onde vem cada valor e guarda os preços do dia do lançamento.
                        </p>

                        <ul class="mt-8 space-y-4">
                            @foreach ([
                                'Insumos: quantidade aplicada × valor unitário.',
                                'Máquinas e mão de obra: horas × custo por hora.',
                                'Implementos sem custo próprio, já incluídos no custo da máquina.',
                                'Custo por hectare: custo total do talhão ÷ área do talhão.',
                            ] as $item)
                                <li class="flex gap-3 text-zinc-700 dark:text-zinc-200">
                                    <flux:icon.check-circle variant="mini" class="mt-0.5 shrink-0 text-success dark:text-accent-content" />
                                    <span>{{ $item }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="relative">
                        <div class="pointer-events-none absolute -inset-6 -z-10 rounded-[2.5rem] bg-linear-to-br from-primary/10 to-accent-green/10 blur-xl dark:from-secondary/20" aria-hidden="true"></div>

                        <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-xl shadow-primary/5 sm:p-8 dark:border-white/10 dark:bg-zinc-800">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-xs font-medium tracking-wide text-zinc-500 uppercase dark:text-zinc-400">Exemplo de atividade</p>
                                    <p class="mt-1 text-lg font-semibold text-zinc-900 dark:text-white">Adubação · Talhão 01</p>
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Soja · 12,50 ha · safra 2025/2026</p>
                                </div>
                                <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-primary/8 text-primary dark:bg-secondary/30 dark:text-accent-content" aria-hidden="true">
                                    <flux:icon.calculator />
                                </span>
                            </div>

                            <dl class="mt-6 divide-y divide-dashed divide-zinc-200 text-sm dark:divide-white/10">
                                @foreach ([
                                    ['Ureia', '250 kg × R$ 3,20', 'R$ 800,00'],
                                    ['Trator', '2,5 h × R$ 180,00', 'R$ 450,00'],
                                    ['Operador', '3 h × R$ 28,50', 'R$ 85,50'],
                                    ['Grade aradora', 'sem custo próprio', 'R$ 0,00'],
                                ] as [$recurso, $conta, $valor])
                                    <div class="flex items-center justify-between gap-4 py-3">
                                        <dt>
                                            <span class="font-medium text-zinc-800 dark:text-zinc-100">{{ $recurso }}</span>
                                            <span class="block text-xs text-zinc-500 dark:text-zinc-400">{{ $conta }}</span>
                                        </dt>
                                        <dd class="font-medium text-zinc-800 tabular-nums dark:text-zinc-100">{{ $valor }}</dd>
                                    </div>
                                @endforeach
                            </dl>

                            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                <div class="rounded-xl bg-zinc-100 p-4 dark:bg-white/5">
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Custo total</p>
                                    <p class="mt-1 text-xl font-semibold text-zinc-900 tabular-nums dark:text-white">R$ 1.335,50</p>
                                </div>
                                <div class="rounded-xl bg-primary p-4 text-white dark:bg-secondary">
                                    <p class="text-xs text-white/75">Custo por hectare</p>
                                    <p class="mt-1 text-xl font-semibold tabular-nums">R$ 106,84</p>
                                </div>
                            </div>

                            <p class="mt-4 text-xs text-zinc-500 dark:text-zinc-400">Valores ilustrativos. R$ 1.335,50 ÷ 12,50 ha = R$ 106,84 por hectare.</p>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Assistente IA --}}
            <section id="assistente" class="relative isolate scroll-mt-20 overflow-hidden bg-linear-to-br from-primary via-primary-dark to-zinc-950 py-24 text-white">
                <div class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(white_1px,transparent_1.5px)] [background-size:24px_24px] opacity-[0.07]" aria-hidden="true"></div>
                <div class="pointer-events-none absolute -top-32 -right-32 -z-10 size-[30rem] rounded-full bg-accent-green/20 blur-3xl" aria-hidden="true"></div>

                <div class="mx-auto grid max-w-7xl items-center gap-14 px-6 lg:grid-cols-2">
                    <div>
                        <p class="text-sm font-semibold tracking-wide text-[#b9dca0] uppercase">Assistente com inteligência artificial</p>
                        <h2 class="mt-3 text-3xl font-semibold tracking-tight text-balance sm:text-4xl">Converse com os dados da sua lavoura.</h2>
                        <p class="mt-5 text-lg leading-8 text-white/75">
                            Pergunte quanto gastou, qual talhão custa mais ou peça para registrar uma atividade. O assistente usa as mesmas regras e cálculos do sistema.
                        </p>

                        <ul class="mt-8 space-y-4">
                            @foreach ([
                                'Consultas em linguagem natural, em português.',
                                'Cadastros sempre com prévia e confirmação antes de gravar.',
                                'Você escolhe o provedor: Claude (Anthropic) ou OpenAI.',
                                'Conecte também o Claude ou o ChatGPT pelo protocolo MCP.',
                            ] as $item)
                                <li class="flex gap-3 text-white/90">
                                    <flux:icon.check-circle variant="mini" class="mt-0.5 shrink-0 text-[#b9dca0]" />
                                    <span>{{ $item }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    {{-- Exemplo de conversa --}}
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-5 shadow-2xl shadow-black/30 backdrop-blur sm:p-6" aria-label="Exemplo de conversa com o assistente">
                        <div class="space-y-5 text-sm leading-6">
                            <div class="flex justify-end">
                                <p class="max-w-[85%] rounded-3xl bg-white/15 px-4 py-2.5">Registre uma adubação de ontem no Talhão 01 com 250 kg de ureia, trator por 2,5 h e o operador por 3 h.</p>
                            </div>

                            <div class="flex gap-3">
                                <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-white/10 ring-1 ring-white/15" aria-hidden="true">
                                    <flux:icon.sparkles variant="micro" class="text-[#b9dca0]" />
                                </span>
                                <div class="min-w-0 text-white/90">
                                    <p>Confira a prévia antes de salvar:</p>
                                    <ul class="mt-2 list-disc space-y-1 ps-5 text-white/80">
                                        <li>Ureia: 250 kg × R$ 3,20 = R$ 800,00</li>
                                        <li>Trator: 2,5 h × R$ 180,00 = R$ 450,00</li>
                                        <li>Operador: 3 h × R$ 28,50 = R$ 85,50</li>
                                    </ul>
                                    <p class="mt-2"><strong class="font-semibold text-white">Custo total: R$ 1.335,50.</strong> Posso salvar?</p>
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <p class="rounded-3xl bg-white/15 px-4 py-2.5">Pode salvar.</p>
                            </div>

                            <div class="flex gap-3">
                                <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-white/10 ring-1 ring-white/15" aria-hidden="true">
                                    <flux:icon.sparkles variant="micro" class="text-[#b9dca0]" />
                                </span>
                                <p class="text-white/90">Atividade registrada no Talhão 01. O custo já aparece no relatório da safra.</p>
                            </div>
                        </div>

                        <div class="mt-6 flex items-center gap-2 rounded-full border border-white/10 bg-black/20 px-4 py-2.5 text-sm text-white/50" aria-hidden="true">
                            Pergunte alguma coisa ou peça um cadastro…
                            <span class="ms-auto flex size-7 items-center justify-center rounded-full bg-white text-primary">
                                <flux:icon.arrow-up variant="micro" />
                            </span>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Perguntas frequentes --}}
            <section id="perguntas" class="scroll-mt-20 py-24">
                <div class="mx-auto max-w-3xl px-6">
                    <div class="text-center">
                        <p class="text-sm font-semibold tracking-wide text-secondary uppercase dark:text-accent-content">Perguntas frequentes</p>
                        <h2 class="mt-3 text-3xl font-semibold tracking-tight text-zinc-900 sm:text-4xl dark:text-white">Ficou alguma dúvida?</h2>
                    </div>

                    <div class="mt-12 divide-y divide-zinc-200 rounded-2xl border border-zinc-200 bg-white dark:divide-white/10 dark:border-white/10 dark:bg-white/5">
                        @foreach ($perguntas as [$pergunta, $resposta])
                            <details class="group px-6 py-5 [&_summary::-webkit-details-marker]:hidden">
                                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-medium text-zinc-900 dark:text-white">
                                    {{ $pergunta }}
                                    <flux:icon.chevron-down variant="mini" class="shrink-0 text-zinc-400 transition group-open:rotate-180" />
                                </summary>
                                <p class="mt-3 text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ $resposta }}</p>
                            </details>
                        @endforeach
                    </div>
                </div>
            </section>

            {{-- Chamada final --}}
            <section class="px-6 pb-24">
                <div class="relative isolate mx-auto max-w-5xl overflow-hidden rounded-3xl bg-linear-to-br from-primary via-secondary to-success px-6 py-16 text-center text-white shadow-2xl shadow-primary/20 sm:px-16">
                    <div class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(white_1px,transparent_1.5px)] [background-size:22px_22px] opacity-10" aria-hidden="true"></div>

                    <h2 class="text-3xl font-semibold tracking-tight text-balance sm:text-4xl">Comece hoje a controlar os custos da sua lavoura.</h2>
                    <p class="mx-auto mt-4 max-w-xl text-lg text-white/80">Crie sua conta, cadastre a propriedade e registre a primeira atividade em poucos minutos.</p>

                    <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                        @auth
                            <a href="{{ route('dashboard') }}" class="inline-flex h-12 items-center gap-2 rounded-lg bg-white px-6 font-medium text-primary shadow-sm transition hover:bg-zinc-100">
                                Ir para o Dashboard
                                <flux:icon.arrow-right variant="mini" />
                            </a>
                        @else
                            <a href="{{ route('register') }}" class="inline-flex h-12 items-center gap-2 rounded-lg bg-white px-6 font-medium text-primary shadow-sm transition hover:bg-zinc-100">
                                Criar minha conta
                                <flux:icon.arrow-right variant="mini" />
                            </a>
                            <a href="{{ route('login') }}" class="inline-flex h-12 items-center rounded-lg px-6 font-medium text-white ring-1 ring-white/40 transition hover:bg-white/10">
                                Já tenho conta
                            </a>
                        @endauth
                    </div>
                </div>
            </section>
        </main>

        {{-- Rodapé --}}
        <footer class="border-t border-zinc-200 bg-fundo dark:border-white/10 dark:bg-zinc-950/40">
            <div class="mx-auto flex max-w-7xl flex-col gap-8 px-6 py-12 md:flex-row md:items-center md:justify-between">
                <div>
                    <x-logo-scaa class="h-10" />
                    <p class="mt-4 max-w-sm text-sm text-zinc-500 dark:text-zinc-400">
                        Sistema de Controle de Atividades Agrícolas: apropriação de custos por propriedade e talhão.
                    </p>
                </div>

                <div class="text-sm text-zinc-500 md:text-end dark:text-zinc-400">
                    <p>Trabalho de Conclusão de Curso · FATEC Bebedouro</p>
                    <p>Curso de Big Data no Agronegócio</p>
                    <p class="mt-2">© {{ now()->year }} SCAA</p>
                </div>
            </div>
        </footer>

        @fluxScripts
    </body>
</html>
