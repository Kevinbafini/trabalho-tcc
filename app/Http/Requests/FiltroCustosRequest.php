<?php

namespace App\Http\Requests;

use App\Models\Propriedade;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Filtros do dashboard, da tela de custos por talhão e dos relatórios.
 * Só aceita propriedades e talhões do usuário logado.
 *
 * @phpstan-type FiltrosValidados array{
 *     propriedade_id: int|null,
 *     talhao_id: int|null,
 *     safra: string|null,
 *     data_inicio: string|null,
 *     data_fim: string|null,
 * }
 */
class FiltroCustosRequest extends FormRequest
{
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
        $propriedadesDoUsuario = Propriedade::query()
            ->where('user_id', $this->user()?->id)
            ->pluck('id')
            ->all();

        return [
            'propriedade_id' => ['nullable', 'integer', Rule::in($propriedadesDoUsuario)],
            'talhao_id' => ['nullable', 'integer', Rule::exists('talhoes', 'id')->whereIn('propriedade_id', $propriedadesDoUsuario)],
            'safra' => ['nullable', 'string', 'max:9'],
            'data_inicio' => ['nullable', 'date'],
            'data_fim' => ['nullable', 'date', 'after_or_equal:data_inicio'],
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
            'data_inicio' => 'data inicial',
            'data_fim' => 'data final',
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
            'data_fim.after_or_equal' => 'A data final deve ser igual ou posterior à data inicial.',
        ];
    }

    /**
     * Filtros validados, com campos vazios convertidos em nulo.
     *
     * @return FiltrosValidados
     */
    public function filtros(): array
    {
        return [
            'propriedade_id' => $this->filled('propriedade_id') ? $this->integer('propriedade_id') : null,
            'talhao_id' => $this->filled('talhao_id') ? $this->integer('talhao_id') : null,
            'safra' => $this->filled('safra') ? $this->string('safra')->toString() : null,
            'data_inicio' => $this->filled('data_inicio') ? $this->string('data_inicio')->toString() : null,
            'data_fim' => $this->filled('data_fim') ? $this->string('data_fim')->toString() : null,
        ];
    }
}
