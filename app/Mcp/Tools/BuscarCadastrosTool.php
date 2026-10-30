<?php

namespace App\Mcp\Tools;

use App\Models\Funcionario;
use App\Models\Implemento;
use App\Models\Insumo;
use App\Models\Maquina;
use App\Models\Propriedade;
use App\Models\Talhao;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Number;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[IsIdempotent]
#[IsOpenWorld(false)]
#[Name('buscar-cadastros')]
#[Title('Buscar cadastros')]
#[Description('Busca propriedades, talhões, funcionários, máquinas, implementos e insumos do usuário, com IDs, valores e situação. Aceita vários tipos de uma vez: use antes de cadastrar ou registrar uma atividade para obter os IDs a partir dos nomes que o usuário citou.')]
class BuscarCadastrosTool extends Ferramenta
{
    /** Máximo de registros devolvidos por tipo. */
    private const LIMITE_POR_TIPO = 30;

    /**
     * Tipos que podem ser buscados.
     *
     * @var list<string>
     */
    public const TIPOS = ['propriedades', 'talhoes', 'funcionarios', 'maquinas', 'implementos', 'insumos'];

    protected function executar(Request $request, User $usuario): Response
    {
        $validado = $request->validate([
            'tipos' => ['required', 'array', 'min:1'],
            'tipos.*' => ['string', Rule::in(self::TIPOS)],
            'termo' => ['nullable', 'string', 'max:100'],
            'propriedade_id' => ['nullable', 'integer'],
            'incluir_inativos' => ['nullable', 'boolean'],
        ], attributes: ['tipos.*' => 'tipo']);

        $termo = trim((string) ($validado['termo'] ?? ''));
        $propriedadeId = isset($validado['propriedade_id']) ? (int) $validado['propriedade_id'] : null;
        $incluirInativos = (bool) ($validado['incluir_inativos'] ?? false);

        $resultado = [];

        foreach (array_unique($validado['tipos']) as $tipo) {
            $resultado[$tipo] = $this->buscar($tipo, $usuario, $termo, $propriedadeId, $incluirInativos);
        }

        return Response::json($resultado);
    }

    /**
     * Registros de um tipo, somente entre os dados do usuário.
     *
     * @return list<array<string, mixed>>
     */
    private function buscar(string $tipo, User $usuario, string $termo, ?int $propriedadeId, bool $incluirInativos): array
    {
        if ($tipo === 'propriedades') {
            return array_values(Propriedade::query()
                ->doUsuario($usuario)
                ->buscar($termo)
                ->when($propriedadeId, fn (Builder $query, int $id) => $query->whereKey($id))
                ->orderBy('nome')
                ->limit(self::LIMITE_POR_TIPO)
                ->get()
                ->map(fn (Propriedade $propriedade): array => [
                    'id' => $propriedade->id,
                    'nome' => $propriedade->nome,
                    'municipio' => $propriedade->municipio.'/'.$propriedade->uf,
                    'area_total' => $this->hectares($propriedade->area_total),
                    'area_livre' => $this->hectares($propriedade->area_total - $propriedade->areaOcupada()),
                ])
                ->all());
        }

        $modelo = match ($tipo) {
            'talhoes' => Talhao::class,
            'funcionarios' => Funcionario::class,
            'maquinas' => Maquina::class,
            'implementos' => Implemento::class,
            default => Insumo::class,
        };

        return array_values($modelo::query()
            ->doUsuario($usuario)
            ->with('propriedade')
            ->buscar($termo)
            ->when($propriedadeId, fn (Builder $query, int $id) => $query->where('propriedade_id', $id))
            ->when(! $incluirInativos, fn (Builder $query) => $query->where('status', '!=', 'inativo'))
            ->orderBy('nome')
            ->limit(self::LIMITE_POR_TIPO)
            ->get()
            ->map(fn (Talhao|Funcionario|Maquina|Implemento|Insumo $registro): array => [
                'id' => $registro->id,
                'nome' => $registro->nome,
                'propriedade_id' => $registro->propriedade_id,
                'propriedade' => $registro->propriedade->nome,
                ...$this->detalhes($registro),
                'situacao' => $registro->status->rotulo(),
            ])
            ->all());
    }

    /**
     * Campos próprios de cada tipo de cadastro.
     *
     * @return array<string, mixed>
     */
    private function detalhes(Talhao|Funcionario|Maquina|Implemento|Insumo $registro): array
    {
        return match (true) {
            $registro instanceof Talhao => [
                'area' => $this->hectares($registro->area),
                'cultura' => $registro->cultura,
                'safra' => $registro->safra,
            ],
            $registro instanceof Funcionario => [
                'funcao' => $registro->funcao,
                'custo_por_hora' => $this->moeda($registro->custo_hora),
            ],
            $registro instanceof Maquina => [
                'tipo' => $registro->tipo,
                'modelo' => $registro->modelo,
                'custo_por_hora' => $this->moeda($registro->custo_hora),
            ],
            $registro instanceof Implemento => [
                'tipo' => $registro->tipo,
                'modelo' => $registro->modelo,
            ],
            default => [
                'categoria' => $registro->categoria->rotulo(),
                'unidade' => $registro->unidade->value,
                'valor_unitario' => $this->moeda($registro->valor_unitario).'/'.$registro->unidade->value,
                'estoque' => Number::format($registro->quantidade_estoque, maxPrecision: 2).' '.$registro->unidade->value,
            ],
        };
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'tipos' => $schema->array()->items($schema->string()->enum(self::TIPOS))->min(1)
                ->description('Tipos de cadastro a buscar (um ou vários).')->required(),
            'termo' => $schema->string()->max(100)->description('Parte do nome (ou cultura, função, tipo) para filtrar. Deixe vazio para listar todos.'),
            'propriedade_id' => $schema->integer()->min(1)->description('Limita a busca a uma propriedade (opcional).'),
            'incluir_inativos' => $schema->boolean()->description('Inclui registros inativos (padrão: false). Novas atividades só devem usar registros ativos.'),
        ];
    }
}
