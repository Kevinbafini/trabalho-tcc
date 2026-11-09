<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Passport\Exceptions\MissingScopeException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // API REST (/api/v1): todo erro volta em JSON no formato {"message": "..."},
        // em português (a validação acrescenta "errors", com as mensagens de cada campo).
        // Fora da API o retorno é null, e o Laravel segue com o tratamento padrão.
        $exceptions->render(function (Throwable $erro, Request $request): ?JsonResponse {
            if (! $request->is('api/*')) {
                return null;
            }

            $causa = $erro->getPrevious();

            return match (true) {
                $erro instanceof ValidationException => response()->json([
                    'message' => 'Os dados enviados são inválidos.',
                    'errors' => $erro->errors(),
                ], $erro->status),

                $erro instanceof AuthenticationException => response()->json([
                    'message' => 'Não autenticado. Envie um token válido no cabeçalho Authorization: Bearer {token}.',
                ], 401),

                $erro instanceof AccessDeniedHttpException => response()->json([
                    'message' => $causa instanceof MissingScopeException
                        ? 'Este token não tem permissão para esta operação. Escopo necessário: '.implode(', ', $causa->scopes()).'.'
                        : 'Acesso negado.',
                ], 403),

                $erro instanceof NotFoundHttpException => response()->json([
                    'message' => $causa instanceof ModelNotFoundException
                        ? 'Registro não encontrado.'
                        : 'Endereço não encontrado na API.',
                ], 404),

                $erro instanceof MethodNotAllowedHttpException => response()->json([
                    'message' => 'Método HTTP não permitido neste endereço.',
                ], 405, $erro->getHeaders()),

                $erro instanceof TooManyRequestsHttpException => response()->json([
                    'message' => 'Muitas requisições. Aguarde um momento e tente novamente.',
                ], 429, $erro->getHeaders()),

                default => null,
            };
        });
    })->create();
