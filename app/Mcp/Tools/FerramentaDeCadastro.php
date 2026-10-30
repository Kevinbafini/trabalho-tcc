<?php

namespace App\Mcp\Tools;

use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

/**
 * Ferramentas que gravam dados. Funcionam em dois passos, para que nada seja
 * salvo sem a confirmação explícita do usuário:
 *
 *   1. confirmado_pelo_usuario = false → valida os dados e devolve uma prévia
 *      (nada é gravado); a IA mostra o resumo e pede a confirmação;
 *   2. confirmado_pelo_usuario = true  → valida de novo e grava.
 *
 * A validação usa o mesmo Form Request da tela de cadastro: as regras, as
 * mensagens e a verificação de que a propriedade pertence ao usuário são
 * exatamente as mesmas do formulário.
 */
abstract class FerramentaDeCadastro extends Ferramenta
{
    /**
     * Form Request da tela de cadastro correspondente.
     *
     * @return class-string<FormRequest>
     */
    abstract protected function formulario(): string;

    /**
     * Argumentos da ferramenta no formato dos campos do formulário.
     *
     * @return array<string, mixed>
     */
    abstract protected function dados(Request $request): array;

    /**
     * Resumo do que será gravado, mostrado ao usuário antes da confirmação.
     *
     * @return array<string, mixed>
     */
    abstract protected function previa(FormRequest $formulario, User $usuario): array;

    /**
     * Grava o registro (somente após a confirmação).
     */
    abstract protected function salvar(FormRequest $formulario, User $usuario): Response;

    protected function executar(Request $request, User $usuario): Response
    {
        $formulario = $this->validarComFormulario($this->formulario(), $this->dados($request), $usuario);

        if (! filter_var($request->get('confirmado_pelo_usuario'), FILTER_VALIDATE_BOOL)) {
            return Response::json([
                'salvo' => false,
                'previa' => $this->previa($formulario, $usuario),
                'proximo_passo' => 'Nada foi salvo. Mostre este resumo ao usuário e peça confirmação explícita. '
                    .'Só chame esta ferramenta de novo, com confirmado_pelo_usuario=true, depois que ele confirmar.',
            ]);
        }

        return $this->salvar($formulario, $usuario);
    }

    /**
     * Campo de confirmação, presente em todas as ferramentas de cadastro.
     */
    protected function campoDeConfirmacao(JsonSchema $schema): Type
    {
        return $schema->boolean()
            ->description('Use false para receber a prévia (nada é salvo). Use true somente quando a mensagem mais recente do usuário confirmar explicitamente a prévia apresentada.')
            ->required();
    }
}
