<x-layouts::app title="Nova propriedade">
    <div class="mx-auto max-w-3xl">
        <x-cabecalho-pagina titulo="Nova propriedade" descricao="Informe os dados da propriedade rural." :voltar="route('propriedades.index')" />

        <form method="POST" action="{{ route('propriedades.store') }}" class="space-y-6">
            @csrf
            @include('propriedades.form')
        </form>
    </div>
</x-layouts::app>
