<x-layouts::app :title="'Editar '.$insumo->nome">
    <x-cabecalho-pagina :titulo="'Editar '.$insumo->nome" descricao="Alterações não mudam os custos de atividades já registradas." :voltar="route('insumos.index')" />

    <form method="POST" action="{{ route('insumos.update', $insumo) }}" class="max-w-2xl">
        @csrf
        @method('PUT')
        @include('insumos.form')
    </form>
</x-layouts::app>
