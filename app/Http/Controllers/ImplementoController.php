<?php

namespace App\Http\Controllers;

use App\Enums\StatusEquipamento;
use App\Http\Requests\ImplementoRequest;
use App\Models\Implemento;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ImplementoController extends Controller
{
    /**
     * Lista os implementos do usuário, com busca e filtro opcional por propriedade.
     */
    public function index(Request $request, #[CurrentUser] User $usuario): View|string
    {
        $filtroPropriedade = $request->integer('propriedade_id') ?: null;
        $busca = $request->string('busca')->trim()->toString();

        $implementos = Implemento::query()
            ->doUsuario($usuario)
            ->with('propriedade')
            ->when($filtroPropriedade, fn (Builder $query, int $id) => $query->where('propriedade_id', $id))
            ->buscar($busca)
            ->orderBy('nome')
            ->paginate(15)
            ->withQueryString();

        return $this->responderListagem($request, view('implementos.index', [
            'implementos' => $implementos,
            'propriedades' => $this->propriedadesDoUsuario($usuario),
            'filtroPropriedade' => $filtroPropriedade,
            'busca' => $busca,
        ]));
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

        return view('implementos.create', [
            'implemento' => new Implemento(['status' => StatusEquipamento::Ativo]),
            'propriedades' => $propriedades,
            'propriedadeSelecionada' => $request->integer('propriedade_id') ?: $propriedades->firstOrFail()->id,
        ]);
    }

    /**
     * Cadastra o implemento na propriedade escolhida.
     */
    public function store(ImplementoRequest $request): RedirectResponse
    {
        $request->propriedadeSelecionada()
            ->implementos()
            ->create($request->safe()->except('propriedade_id'));

        return to_route('implementos.index')
            ->with('sucesso', 'Implemento cadastrado com sucesso.');
    }

    /**
     * Formulário de edição.
     */
    public function edit(Implemento $implemento): View
    {
        return view('implementos.edit', ['implemento' => $implemento->load('propriedade')]);
    }

    /**
     * Atualiza o implemento. Atividades já registradas mantêm os valores do lançamento.
     */
    public function update(ImplementoRequest $request, Implemento $implemento): RedirectResponse
    {
        $implemento->update($request->validated());

        return to_route('implementos.index')
            ->with('sucesso', 'Implemento atualizado com sucesso.');
    }

    /**
     * Exclui o implemento, desde que ainda não tenha sido usado em atividades.
     */
    public function destroy(Implemento $implemento): RedirectResponse
    {
        if ($implemento->atividades()->exists()) {
            return back()->with('erro', 'O implemento "'.$implemento->nome.'" já foi utilizado em atividades e não pode ser excluído, pois isso apagaria o histórico de custos. Para deixar de usá-lo, edite o cadastro e altere a situação para Inativo.');
        }

        $implemento->delete();

        return to_route('implementos.index')
            ->with('sucesso', 'Implemento excluído com sucesso.');
    }
}
