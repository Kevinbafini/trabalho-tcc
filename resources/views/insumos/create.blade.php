<x-layouts::app title="Novo insumo">
    <div class="mx-auto max-w-3xl">
        <x-cabecalho-pagina titulo="Novo insumo" descricao="Informe a categoria, a unidade e o valor unitário." :voltar="route('insumos.index')" />

        <form method="POST" action="{{ route('insumos.store') }}" class="space-y-6">
            @csrf
            @include('insumos.form')
        </form>
    </div>
</x-layouts::app>
