<?php

namespace App\Http\Requests;

use App\Enums\ProvedorIa;
use App\Models\Conversa;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Mensagem enviada ao Assistente IA.
 */
class EnviarMensagemRequest extends FormRequest
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
        return [
            'mensagem' => ['required', 'string', 'max:'.config('assistente.tamanho_da_mensagem')],
            'provedor' => ['required', Rule::enum(ProvedorIa::class)],
            'conversa_id' => ['nullable', 'integer', Rule::exists('conversas', 'id')->where('user_id', $this->user()?->id)],
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
            'mensagem' => 'mensagem',
            'provedor' => 'provedor de IA',
            'conversa_id' => 'conversa',
        ];
    }

    /**
     * Conversa em andamento (somente entre as do usuário) ou null para começar uma nova.
     */
    public function conversa(): ?Conversa
    {
        if (! $this->filled('conversa_id')) {
            return null;
        }

        return Conversa::query()
            ->where('user_id', $this->user()?->id)
            ->findOrFail($this->integer('conversa_id'));
    }

    public function provedor(): ProvedorIa
    {
        return ProvedorIa::from($this->string('provedor')->toString());
    }

    public function texto(): string
    {
        return trim($this->string('mensagem')->toString());
    }
}
