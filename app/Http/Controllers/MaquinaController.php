<?php

namespace App\Http\Controllers;

use App\Enums\StatusEquipamento;
use App\Http\Requests\MaquinaRequest;
use App\Models\Maquina;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MaquinaController extends Controller
{
    /**
     * Lista as máquinas do usuário, com busca e filtro opcional por propriedade.
     */
    public function index(Request $request, #[CurrentUser] User $usuario): View|string
    {
        $filtroPropriedade = $request->integer('propriedade_id') ?: null;
        $busca = $request->string('busca')->trim()->toString();

        $maquinas = Maquina::query()
            ->doUsuario($usuario)
            ->with('propriedade')
            ->when($filtroPropriedade, fn (Builder $query, int $id) => $query->where('propriedade_id', $id))
            ->buscar($busca)
            ->orderBy('nome')
            ->paginate(15)
            ->withQueryString();

        return $this->responderListagem($request, view('maquinas.index', [
            'maquinas' => $maquinas,
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

        return view('maquinas.create', [
            'maquina' => new Maquina(['status' => StatusEquipamento::Ativo]),
            'propriedades' => $propriedades,
            'propriedadeSelecionada' => $request->integer('propriedade_id') ?: $propriedades->firstOrFail()->id,
        ]);
    }

    /**
     * Cadastra a máquina na propriedade escolhida.
     */
    public function store(MaquinaRequest $request): RedirectResponse
    {
        $request->propriedadeSelecionada()
            ->maquinas()
            ->create($request->safe()->except('propriedade_id'));

        return to_route('maquinas.index')
            ->with('sucesso', 'Máquina cadastrada com sucesso.');
    }

    /**
     * Formulário de edição.
     */
    public function edit(Maquina $maquina): View
    {
        return view('maquinas.edit', ['maquina' => $maquina->load('propriedade')]);
    }

    /**
     * Atualiza a máquina. Atividades já registradas mantêm os valores do lançamento.
     */
    public function update(MaquinaRequest $request, Maquina $maquina): RedirectResponse
    {
        $maquina->update($request->validated());

        return to_route('maquinas.index')
            ->with('sucesso', 'Máquina atualizada com sucesso.');
    }

    /**
     * Exclui a máquina, desde que ainda não tenha sido usada em atividades.
     */
    public function destroy(Maquina $maquina): RedirectResponse
    {
        if ($maquina->atividades()->exists()) {
            return back()->with('erro', 'A máquina "'.$maquina->nome.'" já foi utilizada em atividades e não pode ser excluída, pois isso apagaria o histórico de custos. Para deixar de usá-la, edite o cadastro e altere a situação para Inativo.');
        }

        $maquina->delete();

        return to_route('maquinas.index')
            ->with('sucesso', 'Máquina excluída com sucesso.');
    }
}
