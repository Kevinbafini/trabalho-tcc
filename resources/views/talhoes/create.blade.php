<x-layouts::app title="Novo talhão">
    <x-cabecalho-pagina titulo="Novo talhão" descricao="Informe a área, a cultura e a safra do talhão." :voltar="route('talhoes.index')" />

    <form method="POST" action="{{ route('talhoes.store') }}" class="max-w-5xl space-y-10">
        @csrf
        @include('talhoes.form')
    </form>
</x-layouts::app>
