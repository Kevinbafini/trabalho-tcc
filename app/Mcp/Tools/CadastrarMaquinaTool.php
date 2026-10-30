<?php

namespace App\Mcp\Tools;

use App\Enums\StatusEquipamento;
use App\Http\Requests\MaquinaRequest;
use App\Models\Maquina;
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
#[Name('cadastrar-maquina')]
#[Title('Cadastrar máquina')]
#[Description('Cadastra uma máquina (trator, pulverizador, colheitadeira...) em uma propriedade do usuário. O custo por hora forma o custo de maquinário das atividades. Chame primeiro com confirmado_pelo_usuario=false para obter a prévia e só grave após a confirmação explícita do usuário.')]
class CadastrarMaquinaTool extends FerramentaDeCadastro
{
    protected function formulario(): string
    {
        return MaquinaRequest::class;
    }

    protected function dados(Request $request): array
    {
        return [
            ...$request->all(['propriedade_id', 'nome', 'tipo', 'modelo', 'ano', 'custo_hora']),
            'status' => $request->get('status', StatusEquipamento::Ativo->value),
        ];
    }

    protected function previa(FormRequest $formulario, User $usuario): array
    {
        assert($formulario instanceof MaquinaRequest);

        return [
            'propriedade' => $formulario->propriedadeSelecionada()->nome,
            'nome' => $formulario->input('nome'),
            'tipo' => $formulario->input('tipo'),
            'modelo' => $formulario->input('modelo'),
            'ano' => $formulario->input('ano'),
            'custo_por_hora' => $this->moeda($formulario->float('custo_hora')),
            'situacao' => StatusEquipamento::from($formulario->string('status')->toString())->rotulo(),
        ];
    }

    protected function salvar(FormRequest $formulario, User $usuario): Response
    {
        assert($formulario instanceof MaquinaRequest);

        /** @var Maquina $maquina */
        $maquina = $formulario->propriedadeSelecionada()
            ->maquinas()
            ->create($formulario->safe()->except('propriedade_id'));

        return Response::json([
            'salvo' => true,
            'mensagem' => 'Máquina cadastrada.',
            'maquina' => ['id' => $maquina->id, 'nome' => $maquina->nome],
            'link' => route('maquinas.edit', $maquina),
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
            'nome' => $schema->string()->max(100)->description('Nome da máquina (ex.: Trator John Deere 6110J).')->required(),
            'tipo' => $schema->string()->max(60)->description('Tipo (ex.: Trator, Pulverizador, Colheitadeira).')->required(),
            'modelo' => $schema->string()->max(100)->description('Modelo (opcional).'),
            'ano' => $schema->integer()->min(1950)->description('Ano de fabricação (opcional).'),
            'custo_hora' => $schema->number()->min(0)->description('Custo de uma hora de uso, em reais, já incluindo os implementos que ela traciona.')->required(),
            'status' => $schema->string()->enum(StatusEquipamento::class)->description('Situação: ativo (padrão), em_manutencao ou inativo.'),
        ];
    }
}
