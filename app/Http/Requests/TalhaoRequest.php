<?php

namespace App\Http\Requests;

use App\Concerns\ValidaPropriedadeDoUsuario;
use App\Enums\StatusTalhao;
use App\Models\Talhao;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Number;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class TalhaoRequest extends FormRequest
{
    use ValidaPropriedadeDoUsuario;

    /**
     * Determine if the user is authorized to make this request.
     *
     * O talhão em edição já foi localizado entre os do usuário logado.
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
        return [
            'propriedade_id' => $this->regrasDaPropriedade(),
            'nome' => [
                'required',
                'string',
                'max:100',
                Rule::unique('talhoes')
                    ->where('propriedade_id', $this->idDaPropriedade())
                    ->ignore($this->talhaoEmEdicao()),
            ],
            'area' => ['required', 'numeric', 'gt:0', 'max:9999999'],
            'cultura' => ['required', 'string', 'max:60'],
            'safra' => ['required', 'regex:/^\d{4}\/\d{4}$/'],
            'status' => ['required', Rule::enum(StatusTalhao::class)],
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
            'nome' => 'nome do talhão',
            'area' => 'área',
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
            'nome.unique' => 'Já existe um talhão com este nome nesta propriedade.',
            'safra.regex' => 'Informe a safra no formato ano/ano, por exemplo: 2025/2026.',
        ];
    }

    /**
     * A soma das áreas dos talhões ativos não pode ultrapassar a área total da propriedade.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['propriedade_id', 'area', 'status'])) {
                    return;
                }

                // Talhões inativos não ocupam área da propriedade.
                if ($this->input('status') === StatusTalhao::Inativo->value) {
                    return;
                }

                $talhao = $this->talhaoEmEdicao();
                $propriedade = $talhao !== null ? $talhao->propriedade : $this->propriedadeSelecionada();

                $areaOcupada = $propriedade->areaOcupada(exceto: $talhao);
                $areaDisponivel = max(0, round($propriedade->area_total - $areaOcupada, 2));

                if (round($this->float('area'), 2) > $areaDisponivel) {
                    $validator->errors()->add('area', sprintf(
                        'A área informada ultrapassa a área disponível da propriedade. Área total: %s ha · ocupada por talhões ativos: %s ha · disponível: %s ha.',
                        Number::format($propriedade->area_total, 2),
                        Number::format($areaOcupada, 2),
                        Number::format($areaDisponivel, 2),
                    ));
                }
            },
        ];
    }

    /**
     * Talhão sendo editado (nulo no cadastro).
     */
    private function talhaoEmEdicao(): ?Talhao
    {
        $talhao = $this->route('talhao');

        return $talhao instanceof Talhao ? $talhao : null;
    }

    /**
     * Propriedade do talhão: a do talhão em edição ou a escolhida no cadastro.
     */
    private function idDaPropriedade(): int
    {
        return $this->talhaoEmEdicao()->propriedade_id ?? $this->integer('propriedade_id');
    }
}
