<?php

namespace App\Mcp\Tools;

use App\Enums\StatusTalhao;
use App\Http\Requests\TalhaoRequest;
use App\Models\Talhao;
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
#[Name('cadastrar-talhao')]
#[Title('Cadastrar talhão')]
#[Description('Cadastra um talhão em uma propriedade do usuário. A soma das áreas dos talhões ativos não pode passar da área da propriedade. Chame primeiro com confirmado_pelo_usuario=false para obter a prévia e só grave após a confirmação explícita do usuário.')]
class CadastrarTalhaoTool extends FerramentaDeCadastro
{
    protected function formulario(): string
    {
        return TalhaoRequest::class;
    }

    protected function dados(Request $request): array
    {
        return [
            ...$request->all(['propriedade_id', 'nome', 'area', 'cultura', 'safra']),
            'status' => $request->get('status', StatusTalhao::EmCultivo->value),
        ];
    }

    protected function previa(FormRequest $formulario, User $usuario): array
    {
        assert($formulario instanceof TalhaoRequest);

        $propriedade = $formulario->propriedadeSelecionada();

        return [
            'propriedade' => $propriedade->nome,
            'nome' => $formulario->input('nome'),
            'area' => $this->hectares($formulario->float('area')),
            'cultura' => $formulario->input('cultura'),
            'safra' => $formulario->input('safra'),
            'situacao' => StatusTalhao::from($formulario->string('status')->toString())->rotulo(),
            'area_livre_na_propriedade_antes_deste_talhao' => $this->hectares($propriedade->area_total - $propriedade->areaOcupada()),
        ];
    }

    protected function salvar(FormRequest $formulario, User $usuario): Response
    {
        assert($formulario instanceof TalhaoRequest);

        /** @var Talhao $talhao */
        $talhao = $formulario->propriedadeSelecionada()
            ->talhoes()
            ->create($formulario->safe()->except('propriedade_id'));

        return Response::json([
            'salvo' => true,
            'mensagem' => 'Talhão cadastrado.',
            'talhao' => ['id' => $talhao->id, 'nome' => $talhao->nome],
            'link' => route('talhoes.show', $talhao),
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
            'nome' => $schema->string()->max(100)->description('Nome do talhão, único na propriedade (ex.: Talhão 06).')->required(),
            'area' => $schema->number()->min(0.01)->description('Área do talhão em hectares.')->required(),
            'cultura' => $schema->string()->max(60)->description('Cultura plantada (ex.: Soja, Milho, Café).')->required(),
            'safra' => $schema->string()->pattern('^\d{4}/\d{4}$')->description('Safra no formato ano/ano (ex.: 2025/2026).')->required(),
            'status' => $schema->string()->enum(StatusTalhao::class)->description('Situação: em_cultivo (padrão), em_pousio ou inativo.'),
        ];
    }
}
