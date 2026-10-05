{{-- A tela de ajuda é informada por quem inclui o cabeçalho, porque nas atualizações do Livewire a rota atual não é a da tela. --}}
<div class="relative mb-6 w-full">
    <div class="flex items-center gap-2">
        <flux:heading size="xl" level="1">{{ __('Settings') }}</flux:heading>
        <x-ajuda-da-tela :tela="$ajuda ?? null" icone="cog-6-tooth" />
    </div>
    <flux:subheading size="lg" class="mb-6">{{ __('Manage your profile and account settings') }}</flux:subheading>
    <flux:separator variant="subtle" />
</div>
