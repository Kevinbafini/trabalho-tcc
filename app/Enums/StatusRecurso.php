<?php

namespace App\Enums;

/**
 * Situação de funcionários e insumos. Recursos inativos não aparecem
 * para novos lançamentos, mas continuam no histórico das atividades.
 */
enum StatusRecurso: string
{
    case Ativo = 'ativo';
    case Inativo = 'inativo';

    /**
     * Nome exibido para o usuário.
     */
    public function rotulo(): string
    {
        return match ($this) {
            self::Ativo => 'Ativo',
            self::Inativo => 'Inativo',
        };
    }

    /**
     * Cor do selo (badge) exibido nas listagens.
     */
    public function cor(): string
    {
        return match ($this) {
            self::Ativo => 'green',
            self::Inativo => 'zinc',
        };
    }
}
