<x-layouts::app title="Novo insumo">
    <x-cabecalho-pagina titulo="Novo insumo" descricao="Informe a categoria, a unidade e o valor unitário." :voltar="route('insumos.index')" />

    <form method="POST" action="{{ route('insumos.store') }}" class="max-w-5xl space-y-10">
        @csrf
        @include('insumos.form')
    </form>
</x-layouts::app>
