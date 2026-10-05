<?php

namespace App\Enums;

/**
 * Unidades de medida dos insumos. O valor gravado no banco é a sigla.
 */
enum UnidadeMedida: string
{
    case Quilograma = 'kg';
    case Grama = 'g';
    case Tonelada = 't';
    case Litro = 'L';
    case Mililitro = 'mL';
    case Saca = 'sc';
    case Unidade = 'un';

    /**
     * Nome exibido para o usuário.
     */
    public function rotulo(): string
    {
        return match ($this) {
            self::Quilograma => 'Quilograma (kg)',
            self::Grama => 'Grama (g)',
            self::Tonelada => 'Tonelada (t)',
            self::Litro => 'Litro (L)',
            self::Mililitro => 'Mililitro (mL)',
            self::Saca => 'Saca (sc)',
            self::Unidade => 'Unidade (un)',
        };
    }
}
