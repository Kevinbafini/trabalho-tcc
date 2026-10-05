<?php

namespace App\Http\Controllers;

use App\Enums\StatusTalhao;
use App\Http\Requests\TalhaoRequest;
use App\Models\Talhao;
use App\Models\User;
use App\Services\IndicadoresCustoService;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TalhaoController extends Controller
{
    /**
     * Lista os talhões do usuário, com filtro opcional por propriedade.
     */
    public function index(Request $request, #[CurrentUser] User $usuario): View
    {
        $filtroPropriedade = $request->integer('propriedade_id') ?: null;

        $talhoes = Talhao::query()
            ->doUsuario($usuario)
            ->with('propriedade')
            ->when($filtroPropriedade, fn (Builder $query, int $id) => $query->where('propriedade_id', $id))
            ->orderBy('nome')
            ->paginate(15)
            ->withQueryString();

        return view('talhoes.index', [
            'talhoes' => $talhoes,
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

        return view('talhoes.create', [
            'talhao' => new Talhao(['status' => StatusTalhao::EmCultivo]),
            'propriedades' => $propriedades,
            'propriedadeSelecionada' => $request->integer('propriedade_id') ?: $propriedades->firstOrFail()->id,
        ]);
    }

    /**
     * Cadastra o talhão na propriedade escolhida.
     */
    public function store(TalhaoRequest $request): RedirectResponse
    {
        $talhao = $request->propriedadeSelecionada()
            ->talhoes()
            ->create($request->safe()->except('propriedade_id'));

        return to_route('talhoes.show', $talhao)
            ->with('sucesso', 'Talhão cadastrado com sucesso.');
    }

    /**
     * Detalhes do talhão: custos acumulados, custo por hectare e atividades.
     */
    public function show(Talhao $talhao, #[CurrentUser] User $usuario, IndicadoresCustoService $indicadores): View
    {
        $filtros = ['talhao_id' => $talhao->id];

        return view('talhoes.show', [
            'talhao' => $talhao->load('propriedade'),
            'resumo' => $indicadores->resumo($usuario, $filtros),
            'atividades' => $indicadores->atividades($usuario, $filtros)
                ->orderByDesc('data')
                ->orderByDesc('id')
                ->paginate(15),
        ]);
    }

    /**
     * Formulário de edição.
     */
    public function edit(Talhao $talhao): View
    {
        return view('talhoes.edit', ['talhao' => $talhao->load('propriedade')]);
    }

    /**
     * Atualiza o talhão.
     */
    public function update(TalhaoRequest $request, Talhao $talhao): RedirectResponse
    {
        $talhao->update($request->validated());

        return to_route('talhoes.show', $talhao)
            ->with('sucesso', 'Talhão atualizado com sucesso.');
    }

    /**
     * Exclui o talhão, desde que ele não tenha atividades registradas.
     */
    public function destroy(Talhao $talhao): RedirectResponse
    {
        if ($talhao->atividades()->exists()) {
            return back()->with('erro', 'O talhão "'.$talhao->nome.'" possui atividades registradas e não pode ser excluído, pois isso apagaria o histórico de custos. Para deixar de usá-lo, edite o talhão e altere a situação para Inativo.');
        }

        $talhao->delete();

        return to_route('talhoes.index')
            ->with('sucesso', 'Talhão excluído com sucesso.');
    }
}
