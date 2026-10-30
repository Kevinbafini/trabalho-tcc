{{-- Logo do sistema no menu (link para o Dashboard). --}}
@props([
    'sidebar' => false,
])

<a {{ $attributes->class('flex min-w-0 items-center px-2 py-1') }}>
    <x-logo-scaa @class(['h-14' => $sidebar, 'h-9' => ! $sidebar]) />
</a>
