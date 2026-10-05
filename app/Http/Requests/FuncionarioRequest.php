<?php

namespace App\Http\Requests;

use App\Concerns\ValidaPropriedadeDoUsuario;
use App\Enums\StatusRecurso;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FuncionarioRequest extends FormRequest
{
    use ValidaPropriedadeDoUsuario;

    /**
     * Determine if the user is authorized to make this request.
     *
     * O funcionário em edição já foi localizado entre os do usuário logado.
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
            'nome' => ['required', 'string', 'max:150'],
            'funcao' => ['required', 'string', 'max:80'],
            'custo_hora' => ['required', 'numeric', 'min:0', 'max:99999'],
            'status' => ['required', Rule::enum(StatusRecurso::class)],
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
            'funcao' => 'função',
            'custo_hora' => 'custo por hora',
        ];
    }
}
