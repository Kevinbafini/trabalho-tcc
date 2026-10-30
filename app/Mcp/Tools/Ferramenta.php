<?php

namespace App\Mcp\Tools;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Number;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

/**
 * Base das ferramentas do assistente de IA.
 *
 * As mesmas classes servem o chat do sistema (Assistente IA) e o servidor MCP
 * (/mcp), usado por apps externos como o Claude e o ChatGPT. Nos dois casos o
 * usuário vem da autenticação — sessão no chat, token OAuth no MCP — e toda
 * consulta parte somente dos dados dele, como nas telas.
 */
abstract class Ferramenta extends Tool
{
    /**
     * Executa a ferramenta para o usuário autenticado.
     */
    abstract protected function executar(Request $request, User $usuario): Response;

    /**
     * Sem usuário autenticado (ex.: servidor MCP local, pelo terminal), a
     * ferramenta nem aparece na lista.
     */
    public function shouldRegister(Request $request): bool
    {
        return $request->user() instanceof User;
    }

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $usuario = $request->user();

        if (! $usuario instanceof User) {
            return Response::error('É necessário estar autenticado no sistema.');
        }

        try {
            return $this->executar($request, $usuario);
        } catch (ValidationException $erro) {
            // A IA lê as mensagens e pede ao usuário o dado que faltou ou corrige o pedido.
            return Response::error('Dados inválidos: '.collect($erro->errors())->flatten()->implode(' '));
        }
    }

    /**
     * Valida os dados com o Form Request de uma tela, como se o formulário
     * tivesse sido enviado pelo usuário autenticado: mesmas regras, mensagens
     * e verificações de que os registros pertencem a ele. Em caso de erro,
     * lança ValidationException (tratada em handle()).
     *
     * @template TFormulario of FormRequest
     *
     * @param  class-string<TFormulario>  $classe
     * @param  array<string, mixed>  $dados
     * @return TFormulario
     */
    protected function validarComFormulario(string $classe, array $dados, User $usuario, string $metodo = 'POST'): FormRequest
    {
        $formulario = $classe::create('/', $metodo, $dados);
        $formulario->setContainer(app())->setRedirector(app('redirect'));
        $formulario->setUserResolver(fn (): User => $usuario);
        $formulario->validateResolved();

        return $formulario;
    }

    /**
     * Valor em reais (ex.: R$ 1.234,56). A IA repassa o texto pronto, sem refazer contas.
     * O espaço não separável que o formatador usa após "R$" vira um espaço comum.
     */
    protected function moeda(float $valor): string
    {
        $texto = Number::currency($valor) ?: 'R$ '.number_format($valor, 2, ',', '.');

        return str_replace("\u{00A0}", ' ', $texto);
    }

    /**
     * Área em hectares (ex.: 12,50 ha).
     */
    protected function hectares(float $area): string
    {
        return Number::format($area, 2).' ha';
    }
}
