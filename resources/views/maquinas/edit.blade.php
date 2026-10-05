<x-layouts::app :title="'Editar '.$maquina->nome">
    <div class="mx-auto max-w-3xl">
        <x-cabecalho-pagina :titulo="'Editar '.$maquina->nome" descricao="Alterações não mudam os custos de atividades já registradas." :voltar="route('maquinas.index')" />

        <form method="POST" action="{{ route('maquinas.update', $maquina) }}" class="space-y-6">
            @csrf
            @method('PUT')
            @include('maquinas.form')
        </form>
    </div>
</x-layouts::app>
