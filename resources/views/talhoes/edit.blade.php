<x-layouts::app title="Editar talhão">
    <div class="mx-auto max-w-3xl">
        <x-cabecalho-pagina :titulo="'Editar '.$talhao->nome" descricao="Alterar a safra do talhão não muda a safra das atividades já registradas." :voltar="route('talhoes.show', $talhao)" />

        <form method="POST" action="{{ route('talhoes.update', $talhao) }}" class="space-y-6">
            @csrf
            @method('PUT')
            @include('talhoes.form')
        </form>
    </div>
</x-layouts::app>
