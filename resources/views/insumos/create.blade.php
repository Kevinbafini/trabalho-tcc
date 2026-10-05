<x-layouts::app title="Novo insumo">
    <x-cabecalho-pagina titulo="Novo insumo" descricao="Informe a categoria, a unidade e o valor unitário." :voltar="route('insumos.index')" />

    <form method="POST" action="{{ route('insumos.store') }}" class="max-w-2xl">
        @csrf
        @include('insumos.form')
    </form>
</x-layouts::app>
