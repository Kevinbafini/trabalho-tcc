<x-layouts::app title="Nova máquina">
    <x-cabecalho-pagina titulo="Nova máquina" descricao="Informe o tipo e o custo por hora de uso." :voltar="route('maquinas.index')" />

    <form method="POST" action="{{ route('maquinas.store') }}" class="max-w-5xl space-y-10">
        @csrf
        @include('maquinas.form')
    </form>
</x-layouts::app>
