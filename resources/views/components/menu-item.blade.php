{{--
    Item do menu lateral.

    Enquanto a rota do módulo ainda não foi criada, o item aparece
    esmaecido e sem link. Assim que a rota existir, ele vira um link
    automaticamente, sem precisar alterar a sidebar.
--}}
@props([
    'rota',
    'icone',
])

@if (Route::has($rota))
    <flux:sidebar.item
        :icon="$icone"
        :href="route($rota)"
        :current="request()->routeIs($rota, Str::beforeLast($rota, '.').'.*')"
        wire:navigate
    >
        {{ $slot }}
    </flux:sidebar.item>
@else
    <flux:sidebar.item :icon="$icone" title="Módulo disponível em breve" class="cursor-not-allowed opacity-50">
        {{ $slot }}
    </flux:sidebar.item>
@endif
