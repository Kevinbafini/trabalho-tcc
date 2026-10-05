<?php

namespace App\Enums;

/**
 * Categorias de insumos agrícolas.
 */
enum CategoriaInsumo: string
{
    case Semente = 'semente';
    case Fertilizante = 'fertilizante';
    case Corretivo = 'corretivo';
    case Herbicida = 'herbicida';
    case Inseticida = 'inseticida';
    case Fungicida = 'fungicida';
    case Outro = 'outro';

    /**
     * Nome exibido para o usuário.
     */
    public function rotulo(): string
    {
        return match ($this) {
            self::Semente => 'Semente',
            self::Fertilizante => 'Fertilizante',
            self::Corretivo => 'Corretivo de solo',
            self::Herbicida => 'Herbicida',
            self::Inseticida => 'Inseticida',
            self::Fungicida => 'Fungicida',
            self::Outro => 'Outro',
        };
    }
}
