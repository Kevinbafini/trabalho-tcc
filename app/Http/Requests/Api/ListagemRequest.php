<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Parâmetros das listagens da API (query string): busca textual, filtro por
 * propriedade e quantidade de itens por página. A página é escolhida com ?page=N.
 *
 * Um filtro com a propriedade de outro usuário não revela nada: as consultas
 * partem sempre dos dados do usuário do token, e a lista volta vazia.
 */
class ListagemRequest extends FormRequest
{
    /**
     * Itens por página quando o cliente não informa por_pagina (o mesmo das telas).
     */
    public const POR_PAGINA_PADRAO = 15;

    /**
     * Determine if the user is authorized to make this request.
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
            'busca' => ['nullable', 'string', 'max:100'],
            'propriedade_id' => ['nullable', 'integer'],
            'por_pagina' => ['nullable', 'integer', 'between:1,100'],
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
            'por_pagina' => 'itens por página',
        ];
    }

    /**
     * Termo da busca (vazio quando não informado).
     */
    public function busca(): string
    {
        return $this->string('busca')->trim()->toString();
    }

    public function propriedadeId(): ?int
    {
        return $this->filled('propriedade_id') ? $this->integer('propriedade_id') : null;
    }

    public function porPagina(): int
    {
        return $this->filled('por_pagina') ? $this->integer('por_pagina') : self::POR_PAGINA_PADRAO;
    }
}
