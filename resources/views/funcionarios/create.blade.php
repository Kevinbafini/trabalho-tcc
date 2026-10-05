<x-layouts::app title="Novo funcionário">
    <x-cabecalho-pagina titulo="Novo funcionário" descricao="Informe a função e o custo por hora." :voltar="route('funcionarios.index')" />

    <form method="POST" action="{{ route('funcionarios.store') }}" class="max-w-5xl space-y-10">
        @csrf
        @include('funcionarios.form')
    </form>
</x-layouts::app>
