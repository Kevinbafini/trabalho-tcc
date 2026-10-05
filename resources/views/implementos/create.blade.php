<x-layouts::app title="Novo implemento">
    <div class="mx-auto max-w-3xl">
        <x-cabecalho-pagina titulo="Novo implemento" descricao="Implementos não têm custo próprio." :voltar="route('implementos.index')" />

        <form method="POST" action="{{ route('implementos.store') }}" class="space-y-6">
            @csrf
            @include('implementos.form')
        </form>
    </div>
</x-layouts::app>
