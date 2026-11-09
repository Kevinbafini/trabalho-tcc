<?php

namespace App\Services;

use App\Enums\EscopoDaApi;
use Laravel\Passport\Bridge\ScopeRepository;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;

/**
 * Decide quais escopos (permissões) entram em cada token emitido pelo Passport.
 *
 * O sistema emite dois tipos de token:
 *   - o token pessoal da API REST, criado no login da API (POST /api/v1/login);
 *   - o token OAuth dos apps de IA conectados ao servidor MCP (/mcp).
 *
 * Os escopos da API (api:leitura e api:escrita) só entram no token pessoal.
 * Se um app de IA pedir esses escopos na conexão OAuth, recebe o token sem
 * eles: na tela de autorização o usuário aprova as ferramentas do MCP (que só
 * consultam e cadastram), e não a API, que também altera e exclui atividades.
 *
 * Registrado no lugar do repositório padrão do Passport em AppServiceProvider.
 */
class EscoposDosTokens extends ScopeRepository
{
    /**
     * {@inheritdoc}
     */
    public function finalizeScopes(
        array $scopes,
        string $grantType,
        ClientEntityInterface $clientEntity,
        ?string $userIdentifier = null,
        ?string $authCodeId = null,
    ): array {
        $escopos = parent::finalizeScopes($scopes, $grantType, $clientEntity, $userIdentifier, $authCodeId);

        if ($grantType === 'personal_access') {
            return $escopos;
        }

        return array_values(array_filter(
            $escopos,
            fn (ScopeEntityInterface $escopo): bool => EscopoDaApi::tryFrom($escopo->getIdentifier()) === null,
        ));
    }
}
