<x-layouts::app title="Novo implemento">
    <x-cabecalho-pagina titulo="Novo implemento" descricao="Implementos não têm custo próprio." :voltar="route('implementos.index')" />

    <form method="POST" action="{{ route('implementos.store') }}" class="max-w-2xl">
        @csrf
        @include('implementos.form')
    </form>
</x-layouts::app>
