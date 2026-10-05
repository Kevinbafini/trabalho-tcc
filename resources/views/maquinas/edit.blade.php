<x-layouts::app :title="'Editar '.$maquina->nome">
    <x-cabecalho-pagina :titulo="'Editar '.$maquina->nome" descricao="Alterações não mudam os custos de atividades já registradas." :voltar="route('maquinas.index')" />

    <form method="POST" action="{{ route('maquinas.update', $maquina) }}" class="max-w-5xl space-y-10">
        @csrf
        @method('PUT')
        @include('maquinas.form')
    </form>
</x-layouts::app>
