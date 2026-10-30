<?php

namespace App\Mcp\Tools;

use App\Enums\CategoriaInsumo;
use App\Enums\StatusRecurso;
use App\Enums\UnidadeMedida;
use App\Http\Requests\InsumoRequest;
use App\Models\Insumo;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Number;
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
#[Name('cadastrar-insumo')]
#[Title('Cadastrar insumo')]
#[Description('Cadastra um insumo (semente, fertilizante, defensivo...) em uma propriedade do usuário. O valor unitário forma o custo de insumos das atividades; o estoque é apenas informativo. Chame primeiro com confirmado_pelo_usuario=false para obter a prévia e só grave após a confirmação explícita do usuário.')]
class CadastrarInsumoTool extends FerramentaDeCadastro
{
    protected function formulario(): string
    {
        return InsumoRequest::class;
    }

    protected function dados(Request $request): array
    {
        return [
            ...$request->all(['propriedade_id', 'nome', 'categoria', 'unidade', 'valor_unitario']),
            'quantidade_estoque' => $request->get('quantidade_estoque', 0),
            'status' => $request->get('status', StatusRecurso::Ativo->value),
        ];
    }

    protected function previa(FormRequest $formulario, User $usuario): array
    {
        assert($formulario instanceof InsumoRequest);

        $unidade = UnidadeMedida::from($formulario->string('unidade')->toString());

        return [
            'propriedade' => $formulario->propriedadeSelecionada()->nome,
            'nome' => $formulario->input('nome'),
            'categoria' => CategoriaInsumo::from($formulario->string('categoria')->toString())->rotulo(),
            'unidade' => $unidade->rotulo(),
            'valor_unitario' => $this->moeda($formulario->float('valor_unitario')).' por '.$unidade->value,
            'estoque' => Number::format($formulario->float('quantidade_estoque'), 2).' '.$unidade->value,
            'situacao' => StatusRecurso::from($formulario->string('status')->toString())->rotulo(),
        ];
    }

    protected function salvar(FormRequest $formulario, User $usuario): Response
    {
        assert($formulario instanceof InsumoRequest);

        /** @var Insumo $insumo */
        $insumo = $formulario->propriedadeSelecionada()
            ->insumos()
            ->create($formulario->safe()->except('propriedade_id'));

        return Response::json([
            'salvo' => true,
            'mensagem' => 'Insumo cadastrado.',
            'insumo' => ['id' => $insumo->id, 'nome' => $insumo->nome],
            'link' => route('insumos.edit', $insumo),
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
            'nome' => $schema->string()->max(150)->description('Nome do insumo (ex.: Ureia, Glifosato).')->required(),
            'categoria' => $schema->string()->enum(CategoriaInsumo::class)->description('Categoria: semente, fertilizante, corretivo, herbicida, inseticida, fungicida ou outro.')->required(),
            'unidade' => $schema->string()->enum(UnidadeMedida::class)->description('Unidade de medida: kg, g, t, L, mL, sc (saca) ou un (unidade).')->required(),
            'valor_unitario' => $schema->number()->min(0)->description('Preço de UMA unidade de medida, em reais.')->required(),
            'quantidade_estoque' => $schema->number()->min(0)->description('Quantidade em estoque, apenas informativa (padrão: 0).'),
            'status' => $schema->string()->enum(StatusRecurso::class)->description('Situação: ativo (padrão) ou inativo.'),
        ];
    }
}
