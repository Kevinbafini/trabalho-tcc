<x-layouts::app title="Editar atividade">
    <x-cabecalho-pagina
        :titulo="'Editar atividade de '.$atividade->data->format('d/m/Y')"
        descricao="Itens já lançados mantêm o valor unitário do dia do lançamento. Itens novos usam o valor atual do cadastro."
        :voltar="route('atividades.show', $atividade)"
    />

    <form method="POST" action="{{ route('atividades.update', $atividade) }}">
        @csrf
        @method('PUT')
        @include('atividades.form')
    </form>
</x-layouts::app>
