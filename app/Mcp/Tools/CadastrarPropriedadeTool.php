<?php

namespace App\Mcp\Tools;

use App\Http\Requests\PropriedadeRequest;
use App\Models\Propriedade;
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
#[Name('cadastrar-propriedade')]
#[Title('Cadastrar propriedade')]
#[Description('Cadastra uma propriedade rural do usuário. Chame primeiro com confirmado_pelo_usuario=false para obter a prévia (nada é salvo) e só grave após a confirmação explícita do usuário.')]
class CadastrarPropriedadeTool extends FerramentaDeCadastro
{
    protected function formulario(): string
    {
        return PropriedadeRequest::class;
    }

    protected function dados(Request $request): array
    {
        return [
            ...$request->all(['nome', 'proprietario', 'municipio', 'area_total', 'observacoes']),
            'uf' => mb_strtoupper(trim((string) $request->get('uf'))),
        ];
    }

    protected function previa(FormRequest $formulario, User $usuario): array
    {
        return [
            'nome' => $formulario->input('nome'),
            'proprietario' => $formulario->input('proprietario'),
            'municipio' => $formulario->input('municipio').'/'.$formulario->input('uf'),
            'area_total' => $this->hectares($formulario->float('area_total')),
            'observacoes' => $formulario->input('observacoes'),
        ];
    }

    protected function salvar(FormRequest $formulario, User $usuario): Response
    {
        /** @var Propriedade $propriedade */
        $propriedade = $usuario->propriedades()->create($formulario->validated());

        return Response::json([
            'salvo' => true,
            'mensagem' => 'Propriedade cadastrada. O próximo passo é cadastrar os talhões.',
            'propriedade' => ['id' => $propriedade->id, 'nome' => $propriedade->nome],
            'link' => route('propriedades.show', $propriedade),
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
            'nome' => $schema->string()->max(150)->description('Nome da propriedade (ex.: Fazenda Santa Helena).')->required(),
            'proprietario' => $schema->string()->max(150)->description('Nome do proprietário.')->required(),
            'municipio' => $schema->string()->max(100)->description('Município.')->required(),
            'uf' => $schema->string()->enum(Propriedade::UFS)->description('Sigla do estado (ex.: SP).')->required(),
            'area_total' => $schema->number()->min(0.01)->description('Área total em hectares.')->required(),
            'observacoes' => $schema->string()->max(2000)->description('Observações (opcional).'),
        ];
    }
}
