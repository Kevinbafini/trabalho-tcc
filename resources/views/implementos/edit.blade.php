<x-layouts::app :title="'Editar '.$implemento->nome">
    <div class="mx-auto max-w-3xl">
        <x-cabecalho-pagina :titulo="'Editar '.$implemento->nome" descricao="Alterações não mudam os custos de atividades já registradas." :voltar="route('implementos.index')" />

        <form method="POST" action="{{ route('implementos.update', $implemento) }}" class="space-y-6">
            @csrf
            @method('PUT')
            @include('implementos.form')
        </form>
    </div>
</x-layouts::app>
