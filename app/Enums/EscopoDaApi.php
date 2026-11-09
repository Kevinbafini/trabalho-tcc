<?php

namespace App\Enums;

/**
 * Escopos (permissões) de um token da API REST.
 *
 * O token é criado no login da API (POST /api/v1/login) com um ou os dois
 * escopos. Uma rota que exige um escopo que o token não tem responde 403.
 */
enum EscopoDaApi: string
{
    case Leitura = 'api:leitura';
    case Escrita = 'api:escrita';

    /**
     * O que o token pode fazer com o escopo.
     */
    public function descricao(): string
    {
        return match ($this) {
            self::Leitura => 'Consultar propriedades, talhões, recursos, atividades e custos pela API REST',
            self::Escrita => 'Cadastrar, alterar e excluir atividades agrícolas pela API REST',
        };
    }

    /**
     * Identificadores de todos os escopos (ex.: ["api:leitura", "api:escrita"]).
     *
     * @return list<string>
     */
    public static function valores(): array
    {
        return array_map(fn (self $escopo): string => $escopo->value, self::cases());
    }
}
