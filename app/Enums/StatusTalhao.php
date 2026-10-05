<?php

namespace App\Enums;

/**
 * Situação de um talhão. Talhões inativos não ocupam área da propriedade.
 */
enum StatusTalhao: string
{
    case EmCultivo = 'em_cultivo';
    case EmPousio = 'em_pousio';
    case Inativo = 'inativo';

    /**
     * Nome exibido para o usuário.
     */
    public function rotulo(): string
    {
        return match ($this) {
            self::EmCultivo => 'Em cultivo',
            self::EmPousio => 'Em pousio',
            self::Inativo => 'Inativo',
        };
    }

    /**
     * Cor do selo (badge) exibido nas listagens.
     */
    public function cor(): string
    {
        return match ($this) {
            self::EmCultivo => 'green',
            self::EmPousio => 'amber',
            self::Inativo => 'zinc',
        };
    }
}
