<x-layouts::app :title="'Editar '.$implemento->nome">
    <x-cabecalho-pagina :titulo="'Editar '.$implemento->nome" descricao="Alterações não mudam os custos de atividades já registradas." :voltar="route('implementos.index')" />

    <form method="POST" action="{{ route('implementos.update', $implemento) }}" class="max-w-5xl space-y-10">
        @csrf
        @method('PUT')
        @include('implementos.form')
    </form>
</x-layouts::app>
