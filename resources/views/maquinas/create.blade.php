<x-layouts::app title="Nova máquina">
    <div class="mx-auto max-w-3xl">
        <x-cabecalho-pagina titulo="Nova máquina" descricao="Informe o tipo e o custo por hora de uso." :voltar="route('maquinas.index')" />

        <form method="POST" action="{{ route('maquinas.store') }}" class="space-y-6">
            @csrf
            @include('maquinas.form')
        </form>
    </div>
</x-layouts::app>
