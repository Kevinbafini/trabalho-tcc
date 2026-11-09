<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\TipoOperacao;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ListagemDeAtividadesRequest;
use App\Http\Requests\AtividadeAgricolaRequest;
use App\Http\Resources\AtividadeAgricolaResource;
use App\Models\AtividadeAgricola;
use App\Models\User;
use App\Services\CustoAtividadeService;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * CRUD de atividades agrícolas pela API.
 *
 * Usa as mesmas peças da tela de atividades: a validação do
 * AtividadeAgricolaRequest (todos os recursos precisam ser da propriedade da
 * atividade) e o cálculo de custos do CustoAtividadeService. Os custos nunca
 * vêm na requisição: são sempre calculados pelo sistema.
 *
 * A atividade da URL é procurada só entre as do usuário do token
 * (AtividadeAgricola::resolveRouteBinding); a de outro usuário responde 404.
 */
class AtividadeAgricolaController extends Controller
{
    /**
     * Lista as atividades do usuário, das mais recentes para as mais antigas,
     * com busca e filtros por propriedade, talhão e tipo.
     */
    public function index(ListagemDeAtividadesRequest $request, #[CurrentUser] User $usuario): AnonymousResourceCollection
    {
        $atividades = AtividadeAgricola::query()
            ->doUsuario($usuario)
            ->with(['talhao', 'propriedade'])
            ->when($request->propriedadeId(), fn (Builder $query, int $id) => $query->where('propriedade_id', $id))
            ->when($request->talhaoId(), fn (Builder $query, int $id) => $query->where('talhao_id', $id))
            ->when($request->tipo(), fn (Builder $query, TipoOperacao $tipo) => $query->where('tipo', $tipo))
            ->buscar($request->busca())
            ->orderByDesc('data')
            ->orderByDesc('id')
            ->paginate($request->porPagina())
            ->withQueryString();

        return AtividadeAgricolaResource::collection($atividades);
    }

    /**
     * Registra a atividade e responde 201 (Created), com o endereço do novo
     * registro no cabeçalho Location.
     */
    public function store(AtividadeAgricolaRequest $request, CustoAtividadeService $custos): JsonResponse
    {
        $atividade = $custos->salvar(
            new AtividadeAgricola,
            $request->propriedadeSelecionada(),
            $request->dadosDaAtividade(),
        );

        return $this->detalhes($atividade)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED)
            ->header('Location', route('api.v1.atividades.show', $atividade));
    }

    /**
     * Detalhes da atividade, com os itens e os custos gravados no lançamento.
     */
    public function show(AtividadeAgricola $atividade): AtividadeAgricolaResource
    {
        return $this->detalhes($atividade);
    }

    /**
     * Atualiza a atividade (envio completo, como no formulário de edição).
     * Itens que já pertenciam à atividade mantêm o valor do lançamento original.
     */
    public function update(AtividadeAgricolaRequest $request, AtividadeAgricola $atividade, CustoAtividadeService $custos): AtividadeAgricolaResource
    {
        $custos->salvar($atividade, $atividade->propriedade, $request->dadosDaAtividade());

        return $this->detalhes($atividade);
    }

    /**
     * Exclui a atividade e responde 204 (No Content), sem corpo.
     */
    public function destroy(AtividadeAgricola $atividade): Response
    {
        $atividade->delete();

        return response()->noContent();
    }

    /**
     * A atividade com todos os relacionamentos exibidos nos detalhes.
     */
    private function detalhes(AtividadeAgricola $atividade): AtividadeAgricolaResource
    {
        return new AtividadeAgricolaResource(
            $atividade->load(['talhao', 'propriedade', 'insumos', 'maquinas', 'funcionarios', 'implementos']),
        );
    }
}
