<?php

namespace App\Mcp\Tools;

use App\Enums\StatusEquipamento;
use App\Http\Requests\ImplementoRequest;
use App\Models\Implemento;
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
#[Name('cadastrar-implemento')]
#[Title('Cadastrar implemento')]
#[Description('Cadastra um implemento (grade, plantadeira...) em uma propriedade do usuário. Implementos não têm custo próprio: o custo está no custo por hora da máquina. Chame primeiro com confirmado_pelo_usuario=false para obter a prévia e só grave após a confirmação explícita do usuário.')]
class CadastrarImplementoTool extends FerramentaDeCadastro
{
    protected function formulario(): string
    {
        return ImplementoRequest::class;
    }

    protected function dados(Request $request): array
    {
        return [
            ...$request->all(['propriedade_id', 'nome', 'tipo', 'modelo']),
            'status' => $request->get('status', StatusEquipamento::Ativo->value),
        ];
    }

    protected function previa(FormRequest $formulario, User $usuario): array
    {
        assert($formulario instanceof ImplementoRequest);

        return [
            'propriedade' => $formulario->propriedadeSelecionada()->nome,
            'nome' => $formulario->input('nome'),
            'tipo' => $formulario->input('tipo'),
            'modelo' => $formulario->input('modelo'),
            'situacao' => StatusEquipamento::from($formulario->string('status')->toString())->rotulo(),
        ];
    }

    protected function salvar(FormRequest $formulario, User $usuario): Response
    {
        assert($formulario instanceof ImplementoRequest);

        /** @var Implemento $implemento */
        $implemento = $formulario->propriedadeSelecionada()
            ->implementos()
            ->create($formulario->safe()->except('propriedade_id'));

        return Response::json([
            'salvo' => true,
            'mensagem' => 'Implemento cadastrado.',
            'implemento' => ['id' => $implemento->id, 'nome' => $implemento->nome],
            'link' => route('implementos.edit', $implemento),
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
            'nome' => $schema->string()->max(100)->description('Nome do implemento (ex.: Grade aradora).')->required(),
            'tipo' => $schema->string()->max(60)->description('Tipo (ex.: Grade, Plantadeira, Subsolador).')->required(),
            'modelo' => $schema->string()->max(100)->description('Modelo (opcional).'),
            'status' => $schema->string()->enum(StatusEquipamento::class)->description('Situação: ativo (padrão), em_manutencao ou inativo.'),
        ];
    }
}
