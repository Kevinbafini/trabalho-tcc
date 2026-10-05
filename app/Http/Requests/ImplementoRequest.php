<?php

namespace App\Http\Requests;

use App\Concerns\ValidaPropriedadeDoUsuario;
use App\Enums\StatusEquipamento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImplementoRequest extends FormRequest
{
    use ValidaPropriedadeDoUsuario;

    /**
     * Determine if the user is authorized to make this request.
     *
     * O implemento em edição já foi localizado entre os do usuário logado.
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
        ];
    }
}
