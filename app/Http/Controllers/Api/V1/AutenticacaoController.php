<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Resources\UsuarioResource;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Laravel\Passport\AccessToken;

/**
 * Autenticação da API REST com tokens pessoais do Passport.
 *
 * O cliente faz login uma vez, guarda o token e o envia em todas as
 * requisições, no cabeçalho "Authorization: Bearer {token}". A API não usa
 * sessão nem cookies: cada requisição se identifica sozinha pelo token.
 */
class AutenticacaoController extends Controller
{
    /**
     * Confere as credenciais e cria um token de acesso com os escopos pedidos.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $usuario = $request->usuarioAutenticado();
        $resultado = $usuario->createToken($request->nomeDoToken(), $request->escoposSolicitados());
        $token = $resultado->getToken();

        return response()->json([
            'data' => [
                'token' => $resultado->accessToken,
                'tipo' => 'Bearer',
                'expira_em' => $token?->expires_at?->toIso8601String(),
                'escopos' => $token?->scopes,
                'usuario' => new UsuarioResource($usuario),
            ],
        ]);
    }

    /**
     * Revoga o token usado na requisição: a partir daí ele não funciona mais.
     */
    public function logout(#[CurrentUser] User $usuario): Response
    {
        $token = $usuario->currentAccessToken();

        if ($token instanceof AccessToken) {
            $token->revoke();
        }

        return response()->noContent();
    }

    /**
     * Usuário dono do token (útil para conferir se o token está funcionando).
     */
    public function usuario(#[CurrentUser] User $usuario): UsuarioResource
    {
        return new UsuarioResource($usuario);
    }
}
