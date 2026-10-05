<x-layouts::app :title="'Editar '.$insumo->nome">
    <div class="mx-auto max-w-3xl">
        <x-cabecalho-pagina :titulo="'Editar '.$insumo->nome" descricao="Alterações não mudam os custos de atividades já registradas." :voltar="route('insumos.index')" />

        <form method="POST" action="{{ route('insumos.update', $insumo) }}" class="space-y-6">
            @csrf
            @method('PUT')
            @include('insumos.form')
        </form>
    </div>
</x-layouts::app>
