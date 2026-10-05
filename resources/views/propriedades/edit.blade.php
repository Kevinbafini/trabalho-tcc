<x-layouts::app title="Editar propriedade">
    <x-cabecalho-pagina :titulo="'Editar '.$propriedade->nome" descricao="Altere os dados da propriedade." :voltar="route('propriedades.show', $propriedade)" />

    <form method="POST" action="{{ route('propriedades.update', $propriedade) }}" class="max-w-2xl">
        @csrf
        @method('PUT')
        @include('propriedades.form')
    </form>
</x-layouts::app>
