<?php

namespace App\Http\Requests\Api;

use App\Enums\TipoOperacao;
use Illuminate\Validation\Rule;

/**
 * Parâmetros da listagem de atividades: os da listagem comum mais os filtros
 * por talhão e por tipo de operação, os mesmos da tela de atividades.
 */
class ListagemDeAtividadesRequest extends ListagemRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'talhao_id' => ['nullable', 'integer'],
            'tipo' => ['nullable', Rule::enum(TipoOperacao::class)],
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
            ...parent::attributes(),
            'talhao_id' => 'talhão',
            'tipo' => 'tipo de operação',
        ];
    }

    public function talhaoId(): ?int
    {
        return $this->filled('talhao_id') ? $this->integer('talhao_id') : null;
    }

    public function tipo(): ?TipoOperacao
    {
        return $this->enum('tipo', TipoOperacao::class);
    }
}
