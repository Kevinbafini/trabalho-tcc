<?php

namespace App\Data;

/**
 * Resposta final da IA a uma mensagem do usuário, já depois de todas as
 * rodadas de ferramentas. Os tokens somam todas as chamadas feitas à API.
 */
final readonly class RespostaDoAssistente
{
    /**
     * @param  list<string>  $ferramentasUsadas  nomes das ferramentas chamadas, na ordem
     */
    public function __construct(
        public string $conteudo,
        public string $modelo,
        public int $tokensDeEntrada,
        public int $tokensDeSaida,
        public int $tokensEmCache,
        public array $ferramentasUsadas = [],
    ) {}
}
