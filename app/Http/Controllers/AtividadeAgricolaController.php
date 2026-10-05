<?php

namespace App\Http\Controllers;

use App\Enums\StatusEquipamento;
use App\Enums\StatusRecurso;
use App\Enums\StatusTalhao;
use App\Enums\TipoOperacao;
use App\Http\Requests\AtividadeAgricolaRequest;
use App\Models\AtividadeAgricola;
use App\Models\Funcionario;
use App\Models\Insumo;
use App\Models\Maquina;
use App\Models\Propriedade;
use App\Models\Talhao;
use App\Models\User;
use App\Services\CustoAtividadeService;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;
use Illuminate\View\View;

class AtividadeAgricolaController extends Controller
{
    /**
     * Lista as atividades do usuário, com busca e filtros por propriedade, talhão e tipo.
     */
    public function index(Request $request, #[CurrentUser] User $usuario): View|string
    {
        $filtros = [
            'propriedade_id' => $request->integer('propriedade_id') ?: null,
            'talhao_id' => $request->integer('talhao_id') ?: null,
            'tipo' => $request->enum('tipo', TipoOperacao::class),
        ];
        $busca = $request->string('busca')->trim()->toString();

        $consulta = AtividadeAgricola::query()
            ->doUsuario($usuario)
            ->with(['talhao', 'propriedade'])
            ->when($filtros['propriedade_id'], fn (Builder $query, int $id) => $query->where('propriedade_id', $id))
            ->when($filtros['talhao_id'], fn (Builder $query, int $id) => $query->where('talhao_id', $id))
            ->when($filtros['tipo'], fn (Builder $query, TipoOperacao $tipo) => $query->where('tipo', $tipo))
            ->buscar($busca);

        $custoEncontrado = floatval((clone $consulta)->sum('custo_total'));
        $atividades = $consulta->orderByDesc('data')->orderByDesc('id')->paginate(15)->withQueryString();

        return $this->responderListagem($request, view('atividades.index', [
            'atividades' => $atividades,
            'propriedades' => $this->propriedadesDoUsuario($usuario),
            'talhoes' => Talhao::query()->doUsuario($usuario)->with('propriedade')->orderBy('nome')->get(),
            'filtros' => $filtros,
            'busca' => $busca,
            'custoEncontrado' => $custoEncontrado,
        ]));
    }

    /**
     * Formulário de lançamento de uma nova atividade.
     */
    public function create(Request $request, #[CurrentUser] User $usuario): View|RedirectResponse
    {
        $propriedades = $this->propriedadesDoUsuario($usuario);

        if ($propriedades->isEmpty()) {
            return $this->redirecionarParaCadastroDePropriedade();
        }

        $idEscolhido = $request->integer('propriedade_id') ?: intval($request->old('propriedade_id'));
        $propriedade = $propriedades->firstWhere('id', $idEscolhido) ?? $propriedades->firstOrFail();

        if (! $propriedade->talhoes()->where('status', '!=', StatusTalhao::Inativo)->exists()) {
            return to_route('talhoes.create', ['propriedade_id' => $propriedade->id])
                ->with('erro', 'Cadastre um talhão na propriedade "'.$propriedade->nome.'" antes de registrar atividades.');
        }

        $atividade = new AtividadeAgricola(['data' => now()->toDateString()]);

        return view('atividades.create', [
            'atividade' => $atividade,
            'propriedades' => $propriedades,
            'propriedade' => $propriedade,
            'talhaoSelecionado' => intval($request->old('talhao_id') ?? $request->integer('talhao_id')),
            ...$this->dadosDoFormulario($request, $atividade, $propriedade),
        ]);
    }

    /**
     * Registra a atividade. Os custos são calculados no CustoAtividadeService.
     */
    public function store(AtividadeAgricolaRequest $request, CustoAtividadeService $custos): RedirectResponse
    {
        $atividade = $custos->salvar(
            new AtividadeAgricola,
            $request->propriedadeSelecionada(),
            $request->dadosDaAtividade(),
        );

        return to_route('atividades.show', $atividade)
            ->with('sucesso', 'Atividade registrada com sucesso. Custo total calculado: '.Number::currency($atividade->custo_total).'.');
    }

    /**
     * Detalhes da atividade, com os custos gravados no lançamento.
     */
    public function show(AtividadeAgricola $atividade): View
    {
        $atividade->load(['talhao', 'propriedade', 'insumos', 'maquinas', 'funcionarios', 'implementos']);

        return view('atividades.show', ['atividade' => $atividade]);
    }

    /**
     * Formulário de edição.
     */
    public function edit(Request $request, AtividadeAgricola $atividade): View
    {
        $propriedade = $atividade->propriedade;

        return view('atividades.edit', [
            'atividade' => $atividade,
            'propriedade' => $propriedade,
            'talhaoSelecionado' => intval($request->old('talhao_id') ?? $atividade->talhao_id),
            ...$this->dadosDoFormulario($request, $atividade, $propriedade),
        ]);
    }

    /**
     * Atualiza a atividade. Itens já existentes mantêm os valores do lançamento original.
     */
    public function update(AtividadeAgricolaRequest $request, AtividadeAgricola $atividade, CustoAtividadeService $custos): RedirectResponse
    {
        $custos->salvar($atividade, $atividade->propriedade, $request->dadosDaAtividade());

        return to_route('atividades.show', $atividade)
            ->with('sucesso', 'Atividade atualizada com sucesso. Custo total: '.Number::currency($atividade->custo_total).'.');
    }

    /**
     * Exclui a atividade (as associações com recursos são removidas em cascata).
     */
    public function destroy(AtividadeAgricola $atividade): RedirectResponse
    {
        $atividade->delete();

        return to_route('atividades.index')
            ->with('sucesso', 'Atividade excluída com sucesso.');
    }

    /**
     * Dados do formulário dinâmico (Alpine.js): talhões, opções de recursos,
     * valores históricos da atividade e as linhas já preenchidas.
     *
     * Nas opções aparecem os recursos ativos e também os que já pertencem à
     * atividade (mesmo que tenham sido inativados depois do lançamento).
     *
     * @return array<string, mixed>
     */
    private function dadosDoFormulario(Request $request, AtividadeAgricola $atividade, Propriedade $propriedade): array
    {
        $historicoInsumos = $atividade->insumos()->pluck('atividade_insumo.custo_unitario', 'insumos.id');
        $historicoMaquinas = $atividade->maquinas()->pluck('atividade_maquina.custo_hora', 'maquinas.id');
        $historicoFuncionarios = $atividade->funcionarios()->pluck('atividade_funcionario.custo_hora', 'funcionarios.id');
        $implementosDaAtividade = $atividade->implementos()->pluck('implementos.id')->all();

        $insumos = $propriedade->insumos()
            ->where(fn (Builder $query) => $query->where('status', StatusRecurso::Ativo)->orWhereIn('id', $historicoInsumos->keys()))
            ->orderBy('nome')
            ->get();

        $maquinas = $propriedade->maquinas()
            ->where(fn (Builder $query) => $query->where('status', StatusEquipamento::Ativo)->orWhereIn('id', $historicoMaquinas->keys()))
            ->orderBy('nome')
            ->get();

        $funcionarios = $propriedade->funcionarios()
            ->where(fn (Builder $query) => $query->where('status', StatusRecurso::Ativo)->orWhereIn('id', $historicoFuncionarios->keys()))
            ->orderBy('nome')
            ->get();

        $implementos = $propriedade->implementos()
            ->where(fn (Builder $query) => $query->where('status', StatusEquipamento::Ativo)->orWhereIn('id', $implementosDaAtividade))
            ->orderBy('nome')
            ->get();

        $talhoes = $propriedade->talhoes()
            ->where(fn (Builder $query) => $query->where('status', '!=', StatusTalhao::Inativo)->orWhere('id', $atividade->exists ? $atividade->talhao_id : 0))
            ->orderBy('nome')
            ->get();

        // Após um erro de validação, o formulário volta com o que o usuário digitou.
        $voltouComErro = $request->session()->hasOldInput();

        return [
            'talhoes' => $talhoes,
            'implementos' => $implementos,
            'implementosSelecionados' => array_map('intval', (array) $request->old('implementos', $implementosDaAtividade)),
            'formulario' => [
                'opcoes' => [
                    'insumos' => $insumos->map(fn (Insumo $insumo): array => [
                        'id' => $insumo->id,
                        'nome' => $insumo->nome,
                        'unidade' => $insumo->unidade->value,
                        'valor' => $insumo->valor_unitario,
                    ])->values()->all(),
                    'maquinas' => $maquinas->map(fn (Maquina $maquina): array => [
                        'id' => $maquina->id,
                        'nome' => $maquina->nome,
                        'unidade' => 'h',
                        'valor' => $maquina->custo_hora,
                    ])->values()->all(),
                    'funcionarios' => $funcionarios->map(fn (Funcionario $funcionario): array => [
                        'id' => $funcionario->id,
                        'nome' => $funcionario->nome.' — '.$funcionario->funcao,
                        'unidade' => 'h',
                        'valor' => $funcionario->custo_hora,
                    ])->values()->all(),
                ],
                'historico' => [
                    'insumos' => $historicoInsumos->map(fn (mixed $valor): float => floatval($valor))->all(),
                    'maquinas' => $historicoMaquinas->map(fn (mixed $valor): float => floatval($valor))->all(),
                    'funcionarios' => $historicoFuncionarios->map(fn (mixed $valor): float => floatval($valor))->all(),
                ],
                'linhas' => [
                    'insumos' => $voltouComErro
                        ? $this->linhasDigitadas($request->old('insumos'), 'insumo_id', 'quantidade')
                        : $this->linhasGravadas($atividade->insumos()->pluck('atividade_insumo.quantidade', 'insumos.id')),
                    'maquinas' => $voltouComErro
                        ? $this->linhasDigitadas($request->old('maquinas'), 'maquina_id', 'horas')
                        : $this->linhasGravadas($atividade->maquinas()->pluck('atividade_maquina.horas', 'maquinas.id')),
                    'funcionarios' => $voltouComErro
                        ? $this->linhasDigitadas($request->old('funcionarios'), 'funcionario_id', 'horas')
                        : $this->linhasGravadas($atividade->funcionarios()->pluck('atividade_funcionario.horas', 'funcionarios.id')),
                ],
            ],
        ];
    }

    /**
     * Linhas que o usuário havia digitado antes do erro de validação.
     *
     * @return list<array{recurso: string, quantidade: string}>
     */
    private function linhasDigitadas(mixed $linhas, string $campoRecurso, string $campoQuantidade): array
    {
        $resultado = [];

        foreach ((array) $linhas as $linha) {
            if (is_array($linha)) {
                $resultado[] = [
                    'recurso' => strval($linha[$campoRecurso] ?? ''),
                    'quantidade' => strval($linha[$campoQuantidade] ?? ''),
                ];
            }
        }

        return $resultado;
    }

    /**
     * Linhas já gravadas na atividade (edição).
     *
     * @param  Collection<array-key, mixed>  $quantidades  [id do recurso => quantidade ou horas]
     * @return list<array{recurso: string, quantidade: string}>
     */
    private function linhasGravadas(Collection $quantidades): array
    {
        return array_values($quantidades
            ->map(fn (mixed $quantidade, int|string $id): array => [
                'recurso' => strval($id),
                'quantidade' => strval(floatval($quantidade)),
            ])
            ->all());
    }
}
