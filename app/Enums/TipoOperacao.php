<?php

namespace App\Enums;

/**
 * Tipos de operação agrícola que podem ser registrados em uma atividade.
 */
enum TipoOperacao: string
{
    case PreparoSolo = 'preparo_solo';
    case Plantio = 'plantio';
    case Adubacao = 'adubacao';
    case Pulverizacao = 'pulverizacao';
    case Irrigacao = 'irrigacao';
    case TratosCulturais = 'tratos_culturais';
    case Colheita = 'colheita';
    case Outra = 'outra';

    /**
     * Nome exibido para o usuário.
     */
    public function rotulo(): string
    {
        return match ($this) {
            self::PreparoSolo => 'Preparo do solo',
            self::Plantio => 'Plantio',
            self::Adubacao => 'Adubação',
            self::Pulverizacao => 'Pulverização',
            self::Irrigacao => 'Irrigação',
            self::TratosCulturais => 'Tratos culturais',
            self::Colheita => 'Colheita',
            self::Outra => 'Outra',
        };
    }
}
