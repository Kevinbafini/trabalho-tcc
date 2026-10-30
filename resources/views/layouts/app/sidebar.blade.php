<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <x-menu-item rota="dashboard" icone="home">Dashboard</x-menu-item>

                <flux:sidebar.group heading="Gestão" class="grid">
                    <x-menu-item rota="propriedades.index" icone="home-modern">Propriedades</x-menu-item>
                    <x-menu-item rota="talhoes.index" icone="map">Talhões</x-menu-item>
                </flux:sidebar.group>

                <flux:sidebar.group heading="Recursos" class="grid">
                    <x-menu-item rota="funcionarios.index" icone="users">Funcionários</x-menu-item>
                    <x-menu-item rota="maquinas.index" icone="truck">Máquinas</x-menu-item>
                    <x-menu-item rota="implementos.index" icone="wrench-screwdriver">Implementos</x-menu-item>
                    <x-menu-item rota="insumos.index" icone="beaker">Insumos</x-menu-item>
                </flux:sidebar.group>

                <flux:sidebar.group heading="Operações" class="grid">
                    <x-menu-item rota="atividades.index" icone="clipboard-document-list">Atividades Agrícolas</x-menu-item>
                </flux:sidebar.group>

                <flux:sidebar.group heading="Análises" class="grid">
                    <x-menu-item rota="custos-talhoes.index" icone="banknotes">Custos por Talhão</x-menu-item>
                    <x-menu-item rota="relatorios.index" icone="document-chart-bar">Relatórios</x-menu-item>
                </flux:sidebar.group>

                <flux:sidebar.group heading="Inteligência artificial" class="grid">
                    <x-menu-item rota="assistente.index" icone="sparkles">Assistente IA</x-menu-item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
