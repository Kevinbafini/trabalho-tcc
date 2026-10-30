<?php

namespace App\Enums;

/**
 * Quem escreveu a mensagem do chat. Os valores são os mesmos usados pelas
 * APIs do Claude e da OpenAI ("user" e "assistant").
 */
enum PapelMensagem: string
{
    case Usuario = 'user';
    case Assistente = 'assistant';
}
