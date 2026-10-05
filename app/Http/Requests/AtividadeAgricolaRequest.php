<?php

namespace App\Http\Requests;

use App\Concerns\ValidaPropriedadeDoUsuario;
use App\Enums\TipoOperacao;
use App\Models\AtividadeAgricola;
use App\Services\CustoAtividadeService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validação do lançamento de uma atividade agrícola.
 *
 * Todos os recursos (talhão, insumos, máquinas, funcionários e implementos)
 * precisam pertencer à mesma propriedade da atividade. Os custos NÃO vêm do
 * formulário: são calculados no CustoAtividadeService.
 *
 * @phpstan-import-type DadosAtividade from CustoAtividadeService
 */
class AtividadeAgricolaRequest extends FormRequest
{
    use ValidaPropriedadeDoUsuario;

    /**
     * Determine if the user is authorized to make this request.
     *
     * A atividade em edição já foi localizada entre as do usuário logado.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $propriedadeId = $this->idDaPropriedade();

        return [
            'propriedade_id' => $this->regrasDaPropriedade(),
            'talhao_id' => ['required', 'integer', Rule::exists('talhoes', 'id')->where('propriedade_id', $propriedadeId)],
            'data' => ['required', 'date', 'before_or_equal:today'],
            'tipo' => ['required', Rule::enum(TipoOperacao::class)],
            'descricao' => ['nullable', 'string', 'max:1000'],

            'insumos' => ['array'],
            'insumos.*.insumo_id' => ['required', 'integer', 'distinct', Rule::exists('insumos', 'id')->where('propriedade_id', $propriedadeId)],
            'insumos.*.quantidade' => ['required', 'numeric', 'gt:0', 'max:9999999'],

            'maquinas' => ['array'],
            'maquinas.*.maquina_id' => ['required', 'integer', 'distinct', Rule::exists('maquinas', 'id')->where('propriedade_id', $propriedadeId)],
            'maquinas.*.horas' => ['required', 'numeric', 'gt:0', 'max:9999'],

            'funcionarios' => ['array'],
            'funcionarios.*.funcionario_id' => ['required', 'integer', 'distinct', Rule::exists('funcionarios', 'id')->where('propriedade_id', $propriedadeId)],
            'funcionarios.*.horas' => ['required', 'numeric', 'gt:0', 'max:9999'],

            'implementos' => ['array'],
            'implementos.*' => ['integer', 'distinct', Rule::exists('implementos', 'id')->where('propriedade_id', $propriedadeId)],
        ];
    }

    /**
     * Nomes dos campos exibidos nas mensagens de erro.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'propriedade_id' => 'propriedade',
            'talhao_id' => 'talhão',
            'tipo' => 'tipo de operação',
            'descricao' => 'descrição',
            'insumos.*.insumo_id' => 'insumo',
            'insumos.*.quantidade' => 'quantidade',
            'maquinas.*.maquina_id' => 'máquina',
            'maquinas.*.horas' => 'horas da máquina',
            'funcionarios.*.funcionario_id' => 'funcionário',
            'funcionarios.*.horas' => 'horas trabalhadas',
            'implementos.*' => 'implemento',
        ];
    }

    /**
     * Mensagens específicas deste formulário.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'data.before_or_equal' => 'A data da atividade não pode ser uma data futura.',
            'talhao_id.exists' => 'O talhão selecionado não pertence a esta propriedade.',
            'insumos.*.insumo_id.required' => 'Selecione o insumo.',
            'insumos.*.insumo_id.distinct' => 'Este insumo foi adicionado mais de uma vez.',
            'insumos.*.insumo_id.exists' => 'O insumo selecionado não pertence a esta propriedade.',
            'maquinas.*.maquina_id.required' => 'Selecione a máquina.',
            'maquinas.*.maquina_id.distinct' => 'Esta máquina foi adicionada mais de uma vez.',
            'maquinas.*.maquina_id.exists' => 'A máquina selecionada não pertence a esta propriedade.',
            'funcionarios.*.funcionario_id.required' => 'Selecione o funcionário.',
            'funcionarios.*.funcionario_id.distinct' => 'Este funcionário foi adicionado mais de uma vez.',
            'funcionarios.*.funcionario_id.exists' => 'O funcionário selecionado não pertence a esta propriedade.',
            'implementos.*.exists' => 'O implemento selecionado não pertence a esta propriedade.',
        ];
    }

    /**
     * A atividade precisa de pelo menos um recurso com custo.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $possuiRecursos = collect(['insumos', 'maquinas', 'funcionarios'])
                    ->contains(fn (string $campo): bool => ! empty($this->input($campo)));

                if (! $possuiRecursos) {
                    $validator->errors()->add(
                        'recursos',
                        'Adicione pelo menos um insumo, uma máquina ou um funcionário para calcular o custo da atividade.',
                    );
                }
            },
        ];
    }

    /**
     * Dados validados no formato usado pelo CustoAtividadeService.
     *
     * @return DadosAtividade
     */
    public function dadosDaAtividade(): array
    {
        return [
            'talhao_id' => $this->integer('talhao_id'),
            'data' => $this->string('data')->toString(),
            'tipo' => TipoOperacao::from($this->string('tipo')->toString()),
            'descricao' => $this->filled('descricao') ? $this->string('descricao')->toString() : null,
            'insumos' => $this->linhasDeRecursos('insumos', 'insumo_id', 'quantidade'),
            'maquinas' => $this->linhasDeRecursos('maquinas', 'maquina_id', 'horas'),
            'funcionarios' => $this->linhasDeRecursos('funcionarios', 'funcionario_id', 'horas'),
            'implementos' => array_values(array_map('intval', (array) $this->validated('implementos', []))),
        ];
    }

    /**
     * Converte as linhas de recursos do formulário em [id, quantidade].
     * Para máquinas e funcionários, a quantidade é o número de horas.
     *
     * @return list<array{id: int, quantidade: float}>
     */
    private function linhasDeRecursos(string $campo, string $campoRecurso, string $campoQuantidade): array
    {
        $linhas = [];

        foreach ((array) $this->validated($campo, []) as $linha) {
            if (is_array($linha)) {
                $linhas[] = [
                    'id' => intval($linha[$campoRecurso]),
                    'quantidade' => floatval($linha[$campoQuantidade]),
                ];
            }
        }

        return $linhas;
    }

    /**
     * Propriedade da atividade: a da atividade em edição ou a escolhida no cadastro.
     */
    private function idDaPropriedade(): int
    {
        $atividade = $this->route('atividade');

        return $atividade instanceof AtividadeAgricola
            ? $atividade->propriedade_id
            : $this->integer('propriedade_id');
    }
}
