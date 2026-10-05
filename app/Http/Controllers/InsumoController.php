<?php

namespace App\Http\Controllers;

use App\Enums\StatusRecurso;
use App\Http\Requests\InsumoRequest;
use App\Models\Insumo;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InsumoController extends Controller
{
    /**
     * Lista os insumos do usuário, com filtro opcional por propriedade.
     */
    public function index(Request $request, #[CurrentUser] User $usuario): View
    {
        $filtroPropriedade = $request->integer('propriedade_id') ?: null;

        $insumos = Insumo::query()
            ->doUsuario($usuario)
            ->with('propriedade')
            ->when($filtroPropriedade, fn (Builder $query, int $id) => $query->where('propriedade_id', $id))
            ->orderBy('nome')
            ->paginate(15)
            ->withQueryString();

        return view('insumos.index', [
            'insumos' => $insumos,
            'propriedades' => $this->propriedadesDoUsuario($usuario),
            'filtroPropriedade' => $filtroPropriedade,
        ]);
    }

    /**
     * Formulário de cadastro.
     */
    public function create(Request $request, #[CurrentUser] User $usuario): View|RedirectResponse
    {
        $propriedades = $this->propriedadesDoUsuario($usuario);

        if ($propriedades->isEmpty()) {
            return $this->redirecionarParaCadastroDePropriedade();
        }

        return view('insumos.create', [
            'insumo' => new Insumo(['status' => StatusRecurso::Ativo]),
            'propriedades' => $propriedades,
            'propriedadeSelecionada' => $request->integer('propriedade_id') ?: $propriedades->firstOrFail()->id,
        ]);
    }

    /**
     * Cadastra o insumo na propriedade escolhida.
     */
    public function store(InsumoRequest $request): RedirectResponse
    {
        $request->propriedadeSelecionada()
            ->insumos()
            ->create($request->safe()->except('propriedade_id'));

        return to_route('insumos.index')
            ->with('sucesso', 'Insumo cadastrado com sucesso.');
    }

    /**
     * Formulário de edição.
     */
    public function edit(Insumo $insumo): View
    {
        return view('insumos.edit', ['insumo' => $insumo->load('propriedade')]);
    }

    /**
     * Atualiza o insumo. Atividades já registradas mantêm os valores do lançamento.
     */
    public function update(InsumoRequest $request, Insumo $insumo): RedirectResponse
    {
        $insumo->update($request->validated());

        return to_route('insumos.index')
            ->with('sucesso', 'Insumo atualizado com sucesso.');
    }

    /**
     * Exclui o insumo, desde que ainda não tenha sido usado em atividades.
     */
    public function destroy(Insumo $insumo): RedirectResponse
    {
        if ($insumo->atividades()->exists()) {
            return back()->with('erro', 'O insumo "'.$insumo->nome.'" já foi utilizado em atividades e não pode ser excluído, pois isso apagaria o histórico de custos. Para deixar de usá-lo, edite o cadastro e altere a situação para Inativo.');
        }

        $insumo->delete();

        return to_route('insumos.index')
            ->with('sucesso', 'Insumo excluído com sucesso.');
    }
}
