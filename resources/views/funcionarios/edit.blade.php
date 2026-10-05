<x-layouts::app :title="'Editar '.$funcionario->nome">
    <div class="mx-auto max-w-3xl">
        <x-cabecalho-pagina :titulo="'Editar '.$funcionario->nome" descricao="Alterações não mudam os custos de atividades já registradas." :voltar="route('funcionarios.index')" />

        <form method="POST" action="{{ route('funcionarios.update', $funcionario) }}" class="space-y-6">
            @csrf
            @method('PUT')
            @include('funcionarios.form')
        </form>
    </div>
</x-layouts::app>
