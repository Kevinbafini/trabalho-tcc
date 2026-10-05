<x-layouts::app title="Nova propriedade">
    <x-cabecalho-pagina titulo="Nova propriedade" descricao="Informe os dados da propriedade rural." :voltar="route('propriedades.index')" />

    <form method="POST" action="{{ route('propriedades.store') }}" class="max-w-5xl space-y-10">
        @csrf
        @include('propriedades.form')
    </form>
</x-layouts::app>
