<?php

namespace App\Http\Requests;

use App\Models\Propriedade;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Number;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PropriedadeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * A propriedade em edição já foi localizada entre as do usuário logado.
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
            'nome' => ['required', 'string', 'max:150'],
            'proprietario' => ['required', 'string', 'max:150'],
            'municipio' => ['required', 'string', 'max:100'],
            'uf' => ['required', Rule::in(Propriedade::UFS)],
            'area_total' => ['required', 'numeric', 'gt:0', 'max:9999999'],
            'observacoes' => ['nullable', 'string', 'max:2000'],
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
            'nome' => 'nome da propriedade',
            'proprietario' => 'proprietário',
            'municipio' => 'município',
            'uf' => 'UF',
            'area_total' => 'área total',
            'observacoes' => 'observações',
        ];
    }

    /**
     * Na edição, a área total não pode ficar menor que a área já ocupada pelos talhões ativos.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $propriedade = $this->route('propriedade');

                if (! $propriedade instanceof Propriedade || $validator->errors()->has('area_total')) {
                    return;
                }

                $areaOcupada = $propriedade->areaOcupada();

                if ($this->float('area_total') < $areaOcupada) {
                    $validator->errors()->add(
                        'area_total',
                        'A área total não pode ser menor que a soma das áreas dos talhões ativos desta propriedade ('.Number::format($areaOcupada, 2).' ha).',
                    );
                }
            },
        ];
    }
}
