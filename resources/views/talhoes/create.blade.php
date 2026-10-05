<x-layouts::app title="Novo talhão">
    <div class="mx-auto max-w-3xl">
        <x-cabecalho-pagina titulo="Novo talhão" descricao="Informe a área, a cultura e a safra do talhão." :voltar="route('talhoes.index')" />

        <form method="POST" action="{{ route('talhoes.store') }}" class="space-y-6">
            @csrf
            @include('talhoes.form')
        </form>
    </div>
</x-layouts::app>
