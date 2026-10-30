<?php

namespace App\Services\Ia;

use App\Enums\PapelMensagem;
use App\Enums\ProvedorIa;
use App\Exceptions\AssistenteIndisponivel;
use App\Models\Conversa;
use App\Models\Mensagem;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Fluxo de uma mensagem no chat do Assistente IA:
 *
 *   1. registrarPergunta(): confere se o assistente pode responder (ligado,
 *      provedor configurado e limites de uso) e grava a mensagem do usuário;
 *   2. responder(): envia o histórico recente ao provedor escolhido e grava a
 *      resposta, com o modelo, as ferramentas usadas e os tokens gastos.
 *
 * São dois passos para que a pergunta continue gravada mesmo se a IA falhar:
 * o erro aparece no chat e o usuário pode tentar de novo.
 */
class ChatDoAssistente
{
    public function __construct(private Assistentes $assistentes) {}

    /**
     * @throws AssistenteIndisponivel
     */
    public function registrarPergunta(User $usuario, ?Conversa $conversa, string $texto, ProvedorIa $provedor): Conversa
    {
        if (config('assistente.habilitado') !== true) {
            throw AssistenteIndisponivel::desabilitado();
        }

        if (! $this->assistentes->para($provedor)->configurado()) {
            throw AssistenteIndisponivel::semChave($provedor);
        }

        $this->verificarLimites($usuario);

        $conversa ??= $usuario->conversas()->create([
            'titulo' => Str::limit(Str::squish($texto), 60),
            'provedor' => $provedor,
        ]);

        $conversa->mensagens()->create(['papel' => PapelMensagem::Usuario, 'conteudo' => $texto]);
        $conversa->update(['provedor' => $provedor, 'ultima_mensagem_em' => now()]);

        return $conversa;
    }

    /**
     * @throws AssistenteIndisponivel
     */
    public function responder(User $usuario, Conversa $conversa, ProvedorIa $provedor): Mensagem
    {
        $assistente = $this->assistentes->para($provedor);
        $resposta = $assistente->responder($usuario, $this->historico($conversa));

        /** @var Mensagem $mensagem */
        $mensagem = $conversa->mensagens()->create([
            'papel' => PapelMensagem::Assistente,
            'conteudo' => $resposta->conteudo,
            'provedor' => $provedor,
            'modelo' => $resposta->modelo,
            'ferramentas' => $resposta->ferramentasUsadas === [] ? null : $resposta->ferramentasUsadas,
            'tokens_entrada' => $resposta->tokensDeEntrada,
            'tokens_saida' => $resposta->tokensDeSaida,
            'tokens_cache' => $resposta->tokensEmCache,
        ]);

        $conversa->update(['ultima_mensagem_em' => now()]);

        return $mensagem;
    }

    /**
     * Mensagens que o usuário ainda pode enviar hoje.
     */
    public function mensagensRestantesHoje(User $usuario): int
    {
        return max(0, (int) config('assistente.mensagens_por_dia') - $this->mensagensEnviadasHoje($usuario));
    }

    /**
     * Histórico recente enviado à IA (as últimas mensagens, em ordem). Para
     * economizar tokens, as mensagens mais antigas da conversa ficam de fora.
     *
     * @return list<array{papel: PapelMensagem, conteudo: string}>
     */
    private function historico(Conversa $conversa): array
    {
        $mensagens = $conversa->mensagens()
            ->reorder('id', 'desc')
            ->limit(max(1, (int) config('assistente.historico')))
            ->get()
            ->reverse()
            ->values();

        // A conversa enviada precisa começar por uma mensagem do usuário.
        while ($mensagens->isNotEmpty() && $mensagens->first()->papel !== PapelMensagem::Usuario) {
            $mensagens->shift();
        }

        return array_values($mensagens->map(fn (Mensagem $mensagem): array => [
            'papel' => $mensagem->papel,
            'conteudo' => $mensagem->conteudo,
        ])->all());
    }

    /**
     * Limites de uso por usuário, para controlar o custo com o sistema publicado.
     *
     * @throws AssistenteIndisponivel
     */
    private function verificarLimites(User $usuario): void
    {
        $chave = 'assistente:'.$usuario->id;

        if (RateLimiter::tooManyAttempts($chave, (int) config('assistente.mensagens_por_minuto'))) {
            throw AssistenteIndisponivel::muitasMensagensSeguidas(RateLimiter::availableIn($chave));
        }

        if ($this->mensagensRestantesHoje($usuario) === 0) {
            throw AssistenteIndisponivel::limiteDiario((int) config('assistente.mensagens_por_dia'));
        }

        RateLimiter::hit($chave, 60);
    }

    private function mensagensEnviadasHoje(User $usuario): int
    {
        return Mensagem::query()
            ->where('papel', PapelMensagem::Usuario)
            ->where('created_at', '>=', today())
            ->whereHas('conversa', fn (Builder $query) => $query->where('user_id', $usuario->id))
            ->count();
    }
}
