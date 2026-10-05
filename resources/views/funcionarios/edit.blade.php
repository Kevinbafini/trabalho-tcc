<x-layouts::app :title="'Editar '.$funcionario->nome">
    <x-cabecalho-pagina :titulo="'Editar '.$funcionario->nome" descricao="Alterações não mudam os custos de atividades já registradas." :voltar="route('funcionarios.index')" />

    <form method="POST" action="{{ route('funcionarios.update', $funcionario) }}" class="max-w-2xl">
        @csrf
        @method('PUT')
        @include('funcionarios.form')
    </form>
</x-layouts::app>
