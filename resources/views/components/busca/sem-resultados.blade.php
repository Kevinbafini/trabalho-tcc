{{-- Mensagem exibida quando a busca ou os filtros não encontram nenhum registro. --}}
@props([
    'busca' => '',
])

<x-estado-vazio
    icone="magnifying-glass"
    titulo="Nenhum resultado encontrado"
    :descricao="$busca !== '' ? 'Não encontramos registros para “'.$busca.'”. Confira a digitação ou tente outro termo.' : 'Nenhum registro corresponde aos filtros selecionados.'"
>
    <flux:button icon="x-mark" x-on:click="limpar()">Limpar busca e filtros</flux:button>
</x-estado-vazio>
