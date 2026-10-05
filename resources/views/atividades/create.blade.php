<x-layouts::app title="Registrar atividade">
    <x-cabecalho-pagina
        titulo="Registrar atividade agrícola"
        descricao="Informe o talhão, a operação e os recursos utilizados. O custo é calculado automaticamente."
        :voltar="route('atividades.index')"
    />

    <form method="POST" action="{{ route('atividades.store') }}">
        @csrf
        @include('atividades.form')
    </form>
</x-layouts::app>
