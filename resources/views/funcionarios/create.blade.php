<x-layouts::app title="Novo funcionário">
    <div class="mx-auto max-w-3xl">
        <x-cabecalho-pagina titulo="Novo funcionário" descricao="Informe a função e o custo por hora." :voltar="route('funcionarios.index')" />

        <form method="POST" action="{{ route('funcionarios.store') }}" class="space-y-6">
            @csrf
            @include('funcionarios.form')
        </form>
    </div>
</x-layouts::app>
