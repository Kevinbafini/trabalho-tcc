{{--
    Mensagens da conversa. Com a conversa vazia, o painel lateral mostra as
    sugestões aqui ($comVazio); na tela cheia, o início de conversa fica em volta do campo de mensagem.
--}}
@if ($conversa !== null && $conversa->mensagens->isNotEmpty())
    @foreach ($conversa->mensagens as $mensagem)
        <x-assistente.mensagem :mensagem="$mensagem" wire:key="mensagem-{{ $mensagem->id }}" />
    @endforeach
@elseif ($comVazio ?? true)
    @include('assistente.partials.vazio')
@endif
