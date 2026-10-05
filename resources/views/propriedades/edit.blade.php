<x-layouts::app title="Editar propriedade">
    <div class="mx-auto max-w-3xl">
        <x-cabecalho-pagina :titulo="'Editar '.$propriedade->nome" descricao="Altere os dados da propriedade." :voltar="route('propriedades.show', $propriedade)" />

        <form method="POST" action="{{ route('propriedades.update', $propriedade) }}" class="space-y-6">
            @csrf
            @method('PUT')
            @include('propriedades.form')
        </form>
    </div>
</x-layouts::app>
