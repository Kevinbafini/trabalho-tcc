<?php

namespace App\Concerns;

use App\Models\Propriedade;
use Illuminate\Validation\Rule;

/**
 * Regras comuns aos formulários de registros que pertencem a uma propriedade
 * (talhões, recursos e atividades). Usado pelos Form Requests.
 */
trait ValidaPropriedadeDoUsuario
{
    /**
     * Regras do campo propriedade_id.
     *
     * No cadastro, a propriedade é obrigatória e precisa pertencer ao usuário
     * logado. Na edição, a propriedade não pode ser trocada: o campo é ignorado.
     *
     * @return array<int, mixed>
     */
    protected function regrasDaPropriedade(): array
    {
        if (! $this->isMethod('POST')) {
            return ['exclude'];
        }

        return [
            'required',
            'integer',
            Rule::exists('propriedades', 'id')->where('user_id', $this->user()?->id),
        ];
    }

    /**
     * Propriedade escolhida no cadastro, buscada somente entre as do usuário logado.
     */
    public function propriedadeSelecionada(): Propriedade
    {
        return Propriedade::query()
            ->where('user_id', $this->user()?->id)
            ->findOrFail($this->integer('propriedade_id'));
    }
}
