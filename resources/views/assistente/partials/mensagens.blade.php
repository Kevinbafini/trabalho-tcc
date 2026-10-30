{{-- Lista de mensagens da conversa, ou as sugestões quando a conversa ainda está vazia. --}}
@if ($conversa === null || $conversa->mensagens->isEmpty())
    @include('assistente.partials.vazio')
@else
    @foreach ($conversa->mensagens as $mensagem)
        <x-assistente.mensagem :mensagem="$mensagem" wire:key="mensagem-{{ $mensagem->id }}" />
    @endforeach
@endif
