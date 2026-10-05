<?php

namespace App\Http\Controllers;

use App\Http\Requests\PropriedadeRequest;
use App\Models\Propriedade;
use App\Models\User;
use App\Services\IndicadoresCustoService;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PropriedadeController extends Controller
{
    /**
     * Lista as propriedades do usuário.
     */
    public function index(#[CurrentUser] User $usuario): View
    {
        $propriedades = Propriedade::query()
            ->doUsuario($usuario)
            ->withCount('talhoes')
            ->orderBy('nome')
            ->paginate(15);

        return view('propriedades.index', ['propriedades' => $propriedades]);
    }

    /**
     * Formulário de cadastro.
     */
    public function create(): View
    {
        return view('propriedades.create', ['propriedade' => new Propriedade]);
    }

    /**
     * Cadastra a propriedade para o usuário logado.
     */
    public function store(PropriedadeRequest $request, #[CurrentUser] User $usuario): RedirectResponse
    {
        $propriedade = $usuario->propriedades()->create($request->validated());

        return to_route('propriedades.show', $propriedade)
            ->with('sucesso', 'Propriedade cadastrada com sucesso. O próximo passo é cadastrar os talhões.');
    }

    /**
     * Detalhes da propriedade, com os talhões e seus custos.
     */
    public function show(Propriedade $propriedade, #[CurrentUser] User $usuario, IndicadoresCustoService $indicadores): View
    {
        $propriedade->loadCount(['funcionarios', 'maquinas', 'implementos', 'insumos']);

        return view('propriedades.show', [
            'propriedade' => $propriedade,
            'areaOcupada' => $propriedade->areaOcupada(),
            'resumo' => $indicadores->resumo($usuario, ['propriedade_id' => $propriedade->id]),
            'custosPorTalhao' => $indicadores->custosPorTalhao($usuario, ['propriedade_id' => $propriedade->id]),
        ]);
    }

    /**
     * Formulário de edição.
     */
    public function edit(Propriedade $propriedade): View
    {
        return view('propriedades.edit', ['propriedade' => $propriedade]);
    }

    /**
     * Atualiza a propriedade.
     */
    public function update(PropriedadeRequest $request, Propriedade $propriedade): RedirectResponse
    {
        $propriedade->update($request->validated());

        return to_route('propriedades.show', $propriedade)
            ->with('sucesso', 'Propriedade atualizada com sucesso.');
    }

    /**
     * Exclui a propriedade, desde que ela ainda não tenha dados vinculados.
     */
    public function destroy(Propriedade $propriedade): RedirectResponse
    {
        if ($propriedade->possuiDadosVinculados()) {
            return back()->with('erro', 'A propriedade "'.$propriedade->nome.'" possui talhões, recursos ou atividades cadastrados e não pode ser excluída. Exclua esses registros antes de excluir a propriedade.');
        }

        $propriedade->delete();

        return to_route('propriedades.index')
            ->with('sucesso', 'Propriedade excluída com sucesso.');
    }
}
