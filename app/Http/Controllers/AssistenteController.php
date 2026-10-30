<?php

namespace App\Http\Controllers;

use App\Contracts\AssistenteDeIa;
use App\Exceptions\AssistenteIndisponivel;
use App\Http\Requests\EnviarMensagemRequest;
use App\Models\Conversa;
use App\Models\User;
use App\Services\Ia\Assistentes;
use App\Services\Ia\ChatDoAssistente;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Chat do Assistente IA: tela cheia (index), painel lateral aberto de
 * qualquer tela (painel), envio de mensagens (store) e exclusão de conversas.
 *
 * O envio é feito pela tela em segundo plano (fetch) e recebe de volta o HTML
 * das mensagens já renderizado; sem JavaScript, o formulário funciona com
 * redirecionamento normal.
 */
class AssistenteController extends Controller
{
    public function __construct(
        private Assistentes $assistentes,
        private ChatDoAssistente $chat,
    ) {}

    /**
     * Tela cheia: histórico de conversas e a conversa selecionada.
     */
    public function index(Request $request, #[CurrentUser] User $usuario): View
    {
        $conversa = $request->filled('conversa')
            ? $usuario->conversas()->with('mensagens')->find($request->integer('conversa'))
            : null;

        return view('assistente.index', [
            ...$this->dadosDoChat($usuario, $conversa),
            'conversas' => $usuario->conversas()->latest('ultima_mensagem_em')->latest('id')->limit(50)->get(),
        ]);
    }

    /**
     * Conteúdo do painel lateral: a conversa pedida, uma nova ou a mais recente.
     */
    public function painel(Request $request, #[CurrentUser] User $usuario): View
    {
        $conversas = $usuario->conversas()->with('mensagens');

        $conversa = match (true) {
            $request->boolean('nova') => null,
            $request->filled('conversa') => $conversas->find($request->integer('conversa')),
            default => $conversas->latest('ultima_mensagem_em')->latest('id')->first(),
        };

        return view('assistente.painel', $this->dadosDoChat($usuario, $conversa));
    }

    /**
     * Envia a mensagem do usuário e devolve a conversa atualizada.
     */
    public function store(EnviarMensagemRequest $request, #[CurrentUser] User $usuario): JsonResponse|RedirectResponse
    {
        // Uma resposta pode encadear várias chamadas à IA; o limite padrão do PHP (30 s) seria curto.
        set_time_limit(180);

        $conversa = $request->conversa();
        $erro = null;

        try {
            $conversa = $this->chat->registrarPergunta($usuario, $conversa, $request->texto(), $request->provedor());
            $this->chat->responder($usuario, $conversa, $request->provedor());
        } catch (AssistenteIndisponivel $falha) {
            $erro = $falha->getMessage();
        }

        if (! $request->expectsJson()) {
            return to_route('assistente.index', array_filter(['conversa' => $conversa?->id]))
                ->with($erro === null ? [] : ['erro' => $erro]);
        }

        $conversa?->load('mensagens');
        $telaCheia = $request->boolean('tela_cheia');

        return response()->json([
            'conversa_id' => $conversa?->id,
            'url' => route('assistente.index', array_filter(['conversa' => $conversa?->id])),
            'mensagens' => view('assistente.partials.mensagens', ['conversa' => $conversa, 'comVazio' => ! $telaCheia])->render(),
            'historico' => $telaCheia
                ? view('assistente.partials.historico', [
                    'conversas' => $usuario->conversas()->latest('ultima_mensagem_em')->latest('id')->limit(50)->get(),
                    'conversaAtual' => $conversa,
                ])->render()
                : null,
            'erro' => $erro,
            'restantes' => $this->chat->mensagensRestantesHoje($usuario),
        ]);
    }

    /**
     * Exclui a conversa e todas as suas mensagens.
     */
    public function destroy(Conversa $conversa): RedirectResponse
    {
        $conversa->delete();

        return to_route('assistente.index')->with('sucesso', 'Conversa excluída.');
    }

    /**
     * Dados comuns à tela cheia e ao painel lateral.
     *
     * @return array<string, mixed>
     */
    private function dadosDoChat(User $usuario, ?Conversa $conversa): array
    {
        return [
            'conversa' => $conversa,
            'provedores' => array_map(fn (AssistenteDeIa $assistente): array => [
                'provedor' => $assistente->provedor(),
                'configurado' => $assistente->configurado(),
                'modelo' => $assistente->modelo(),
            ], $this->assistentes->todos()),
            'provedorSelecionado' => $conversa !== null && $this->assistentes->para($conversa->provedor)->configurado()
                ? $conversa->provedor
                : $this->assistentes->padrao(),
            'disponivel' => $this->assistentes->disponivel(),
            'restantes' => $this->chat->mensagensRestantesHoje($usuario),
        ];
    }
}
