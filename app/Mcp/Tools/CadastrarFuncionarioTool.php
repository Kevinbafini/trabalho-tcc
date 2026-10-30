<?php

namespace App\Mcp\Tools;

use App\Enums\StatusRecurso;
use App\Http\Requests\FuncionarioRequest;
use App\Models\Funcionario;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly(false)]
#[IsDestructive(false)]
#[IsOpenWorld(false)]
#[Name('cadastrar-funcionario')]
#[Title('Cadastrar funcionário')]
#[Description('Cadastra um funcionário em uma propriedade do usuário. O custo por hora forma o custo de mão de obra das atividades. Chame primeiro com confirmado_pelo_usuario=false para obter a prévia e só grave após a confirmação explícita do usuário.')]
class CadastrarFuncionarioTool extends FerramentaDeCadastro
{
    protected function formulario(): string
    {
        return FuncionarioRequest::class;
    }

    protected function dados(Request $request): array
    {
        return [
            ...$request->all(['propriedade_id', 'nome', 'funcao', 'custo_hora']),
            'status' => $request->get('status', StatusRecurso::Ativo->value),
        ];
    }

    protected function previa(FormRequest $formulario, User $usuario): array
    {
        assert($formulario instanceof FuncionarioRequest);

        return [
            'propriedade' => $formulario->propriedadeSelecionada()->nome,
            'nome' => $formulario->input('nome'),
            'funcao' => $formulario->input('funcao'),
            'custo_por_hora' => $this->moeda($formulario->float('custo_hora')),
            'situacao' => StatusRecurso::from($formulario->string('status')->toString())->rotulo(),
        ];
    }

    protected function salvar(FormRequest $formulario, User $usuario): Response
    {
        assert($formulario instanceof FuncionarioRequest);

        /** @var Funcionario $funcionario */
        $funcionario = $formulario->propriedadeSelecionada()
            ->funcionarios()
            ->create($formulario->safe()->except('propriedade_id'));

        return Response::json([
            'salvo' => true,
            'mensagem' => 'Funcionário cadastrado.',
            'funcionario' => ['id' => $funcionario->id, 'nome' => $funcionario->nome],
            'link' => route('funcionarios.edit', $funcionario),
        ]);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'confirmado_pelo_usuario' => $this->campoDeConfirmacao($schema),
            'propriedade_id' => $schema->integer()->min(1)->description('ID da propriedade, obtido antes com buscar-cadastros ou resumo-geral. Nunca invente.')->required(),
            'nome' => $schema->string()->max(150)->description('Nome do funcionário.')->required(),
            'funcao' => $schema->string()->max(80)->description('Função (ex.: Operador de máquinas, Tratorista).')->required(),
            'custo_hora' => $schema->number()->min(0)->description('Custo de uma hora de trabalho, em reais.')->required(),
            'status' => $schema->string()->enum(StatusRecurso::class)->description('Situação: ativo (padrão) ou inativo.'),
        ];
    }
}
