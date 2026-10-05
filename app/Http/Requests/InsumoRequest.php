<?php

namespace App\Http\Requests;

use App\Concerns\ValidaPropriedadeDoUsuario;
use App\Enums\CategoriaInsumo;
use App\Enums\StatusRecurso;
use App\Enums\UnidadeMedida;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InsumoRequest extends FormRequest
{
    use ValidaPropriedadeDoUsuario;

    /**
     * Determine if the user is authorized to make this request.
     *
     * O insumo em edição já foi localizado entre os do usuário logado.
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
            'categoria' => ['required', Rule::enum(CategoriaInsumo::class)],
            'unidade' => ['required', Rule::enum(UnidadeMedida::class)],
            'valor_unitario' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'quantidade_estoque' => ['required', 'numeric', 'min:0', 'max:999999999'],
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
            'valor_unitario' => 'valor unitário',
            'quantidade_estoque' => 'quantidade em estoque',
        ];
    }
}
