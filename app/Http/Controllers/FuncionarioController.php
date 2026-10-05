<?php

namespace App\Http\Controllers;

use App\Enums\StatusRecurso;
use App\Http\Requests\FuncionarioRequest;
use App\Models\Funcionario;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FuncionarioController extends Controller
{
    /**
     * Lista os funcionários do usuário, com busca e filtro opcional por propriedade.
     */
    public function index(Request $request, #[CurrentUser] User $usuario): View|string
    {
        $filtroPropriedade = $request->integer('propriedade_id') ?: null;
        $busca = $request->string('busca')->trim()->toString();

        $funcionarios = Funcionario::query()
            ->doUsuario($usuario)
            ->with('propriedade')
            ->when($filtroPropriedade, fn (Builder $query, int $id) => $query->where('propriedade_id', $id))
            ->buscar($busca)
            ->orderBy('nome')
            ->paginate(15)
            ->withQueryString();

        return $this->responderListagem($request, view('funcionarios.index', [
            'funcionarios' => $funcionarios,
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

        return view('funcionarios.create', [
            'funcionario' => new Funcionario(['status' => StatusRecurso::Ativo]),
            'propriedades' => $propriedades,
            'propriedadeSelecionada' => $request->integer('propriedade_id') ?: $propriedades->firstOrFail()->id,
        ]);
    }

    /**
     * Cadastra o funcionário na propriedade escolhida.
     */
    public function store(FuncionarioRequest $request): RedirectResponse
    {
        $request->propriedadeSelecionada()
            ->funcionarios()
            ->create($request->safe()->except('propriedade_id'));

        return to_route('funcionarios.index')
            ->with('sucesso', 'Funcionário cadastrado com sucesso.');
    }

    /**
     * Formulário de edição.
     */
    public function edit(Funcionario $funcionario): View
    {
        return view('funcionarios.edit', ['funcionario' => $funcionario->load('propriedade')]);
    }

    /**
     * Atualiza o funcionário. Atividades já registradas mantêm os valores do lançamento.
     */
    public function update(FuncionarioRequest $request, Funcionario $funcionario): RedirectResponse
    {
        $funcionario->update($request->validated());

        return to_route('funcionarios.index')
            ->with('sucesso', 'Funcionário atualizado com sucesso.');
    }

    /**
     * Exclui o funcionário, desde que ainda não tenha sido usado em atividades.
     */
    public function destroy(Funcionario $funcionario): RedirectResponse
    {
        if ($funcionario->atividades()->exists()) {
            return back()->with('erro', 'O funcionário "'.$funcionario->nome.'" já participou de atividades e não pode ser excluído, pois isso apagaria o histórico de custos. Para deixar de usá-lo, edite o cadastro e altere a situação para Inativo.');
        }

        $funcionario->delete();

        return to_route('funcionarios.index')
            ->with('sucesso', 'Funcionário excluído com sucesso.');
    }
}
