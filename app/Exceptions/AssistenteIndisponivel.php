<?php

namespace App\Exceptions;

use App\Enums\ProvedorIa;
use RuntimeException;

/**
 * Falha ao obter uma resposta da IA. A mensagem é exibida ao usuário no chat,
 * por isso é sempre amigável e nunca traz detalhes técnicos (esses vão para o log).
 */
class AssistenteIndisponivel extends RuntimeException
{
    public static function desabilitado(): self
    {
        return new self('O assistente de IA está desligado neste sistema.');
    }

    public static function muitasMensagensSeguidas(int $segundos): self
    {
        return new self("Você enviou muitas mensagens seguidas. Aguarde {$segundos} segundos e tente de novo.");
    }

    public static function limiteDiario(int $limite): self
    {
        return new self("Você atingiu o limite de {$limite} mensagens por dia com o assistente. Ele volta a responder amanhã.");
    }

    public static function semChave(ProvedorIa $provedor): self
    {
        return new self("O {$provedor->rotulo()} ainda não está configurado neste sistema. Escolha outro provedor ou peça ao administrador para cadastrar a chave da API.");
    }

    public static function chaveInvalida(ProvedorIa $provedor): self
    {
        return new self("A chave da API do {$provedor->rotulo()} foi recusada. Peça ao administrador para conferir a configuração.");
    }

    public static function foraDoAr(ProvedorIa $provedor): self
    {
        return new self("O {$provedor->rotulo()} está temporariamente indisponível. Tente de novo em alguns instantes ou troque de provedor.");
    }

    public static function respostaInvalida(): self
    {
        return new self('A IA não conseguiu concluir a resposta. Tente reformular o pedido em partes menores.');
    }

    public static function recusada(): self
    {
        return new self('A IA não pode ajudar com este pedido. Tente reformular ou pergunte sobre os dados do sistema.');
    }
}
