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
     * Explicação curta exibida no formulário.
     */
    public function descricao(): string
    {
        return match ($this) {
            self::EmCultivo => 'Ocupa área e recebe atividades.',
            self::EmPousio => 'Ocupa área, mas está sem cultivo.',
            self::Inativo => 'Não ocupa área nem recebe novas atividades.',
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

    /**
     * Ícone exibido junto ao selo da situação.
     */
    public function icone(): string
    {
        return match ($this) {
            self::EmCultivo => 'check-circle',
            self::EmPousio => 'pause-circle',
            self::Inativo => 'minus-circle',
        };
    }
}
