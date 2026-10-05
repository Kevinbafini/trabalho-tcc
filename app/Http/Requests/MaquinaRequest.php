<?php

namespace App\Http\Requests;

use App\Concerns\ValidaPropriedadeDoUsuario;
use App\Enums\StatusEquipamento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MaquinaRequest extends FormRequest
{
    use ValidaPropriedadeDoUsuario;

    /**
     * Determine if the user is authorized to make this request.
     *
     * A máquina em edição já foi localizada entre as do usuário logado.
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
            'nome' => ['required', 'string', 'max:100'],
            'tipo' => ['required', 'string', 'max:60'],
            'modelo' => ['nullable', 'string', 'max:100'],
            'ano' => ['nullable', 'integer', 'between:1950,'.(now()->year + 1)],
            'custo_hora' => ['required', 'numeric', 'min:0', 'max:99999'],
            'status' => ['required', Rule::enum(StatusEquipamento::class)],
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
            'custo_hora' => 'custo por hora',
        ];
    }
}
