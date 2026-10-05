<x-layouts::app title="Editar talhão">
    <x-cabecalho-pagina :titulo="'Editar '.$talhao->nome" descricao="Alterar a safra do talhão não muda a safra das atividades já registradas." :voltar="route('talhoes.show', $talhao)" />

    <form method="POST" action="{{ route('talhoes.update', $talhao) }}" class="max-w-2xl">
        @csrf
        @method('PUT')
        @include('talhoes.form')
    </form>
</x-layouts::app>
