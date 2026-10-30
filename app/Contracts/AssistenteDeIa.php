<?php

namespace App\Contracts;

use App\Data\RespostaDoAssistente;
use App\Enums\PapelMensagem;
use App\Enums\ProvedorIa;
use App\Exceptions\AssistenteIndisponivel;
use App\Models\User;

/**
 * Contrato comum aos provedores de IA (Claude e OpenAI). O chat conversa só
 * com este contrato; cada provedor traduz o histórico e as ferramentas para
 * o formato da sua API.
 */
interface AssistenteDeIa
{
    public function provedor(): ProvedorIa;

    /**
     * Indica se a chave da API do provedor está configurada no .env.
     */
    public function configurado(): bool;

    /**
     * Modelo usado (ex.: claude-haiku-5-5).
     */
    public function modelo(): string;

    /**
     * Gera a próxima resposta da conversa, executando as ferramentas que a IA
     * pedir (em um número limitado de rodadas).
     *
     * @param  list<array{papel: PapelMensagem, conteudo: string}>  $historico  mensagens em ordem, a última é do usuário
     *
     * @throws AssistenteIndisponivel
     */
    public function responder(User $usuario, array $historico): RespostaDoAssistente;
}
