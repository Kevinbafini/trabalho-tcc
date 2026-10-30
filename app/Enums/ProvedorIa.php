<?php

namespace App\Enums;

/**
 * Provedores de IA que o usuário pode escolher no Assistente IA.
 */
enum ProvedorIa: string
{
    case Claude = 'claude';
    case OpenAi = 'openai';

    /**
     * Nome exibido no seletor do chat.
     */
    public function rotulo(): string
    {
        return match ($this) {
            self::Claude => 'Claude',
            self::OpenAi => 'OpenAI',
        };
    }

    /**
     * Empresa responsável pelo modelo.
     */
    public function empresa(): string
    {
        return match ($this) {
            self::Claude => 'Anthropic',
            self::OpenAi => 'OpenAI',
        };
    }
}
