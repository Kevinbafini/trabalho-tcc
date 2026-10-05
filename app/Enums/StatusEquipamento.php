<?php

namespace App\Enums;

/**
 * Situação de máquinas e implementos. Somente equipamentos ativos
 * aparecem para novos lançamentos de atividades.
 */
enum StatusEquipamento: string
{
    case Ativo = 'ativo';
    case EmManutencao = 'em_manutencao';
    case Inativo = 'inativo';

    /**
     * Nome exibido para o usuário.
     */
    public function rotulo(): string
    {
        return match ($this) {
            self::Ativo => 'Ativo',
            self::EmManutencao => 'Em manutenção',
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
            self::EmManutencao => 'amber',
            self::Inativo => 'zinc',
        };
    }
}
