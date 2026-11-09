<?php

namespace App\Http\Requests\Api;

use App\Enums\EscopoDaApi;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;

/**
 * Login da API REST: troca e-mail e senha por um token de acesso.
 *
 * Usa as mesmas credenciais do sistema web. Se a conta tiver a autenticação
 * em duas etapas ligada, o código do aplicativo autenticador também é exigido,
 * para que a API não seja um caminho para contornar essa proteção.
 */
class LoginRequest extends FormRequest
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
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'nome_dispositivo' => ['nullable', 'string', 'max:100'],
            'escopos' => ['nullable', 'array'],
            'escopos.*' => ['string', 'distinct', Rule::enum(EscopoDaApi::class)],
            'codigo_2fa' => ['nullable', 'string', 'max:10'],
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
            'nome_dispositivo' => 'nome do dispositivo',
            'escopos.*' => 'escopo',
            'codigo_2fa' => 'código de autenticação',
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
            'escopos.*.enum' => 'Escopo inválido. Use '.implode(' e/ou ', EscopoDaApi::valores()).'.',
        ];
    }

    /**
     * Confere o e-mail, a senha e, se a conta usa autenticação em duas etapas,
     * o código do aplicativo autenticador. Devolve o usuário dono da conta.
     *
     * @throws ValidationException
     */
    public function usuarioAutenticado(): User
    {
        $usuario = User::query()->where('email', $this->string('email')->toString())->first();

        if (! $usuario || ! Hash::check($this->string('password')->toString(), $usuario->password)) {
            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        if ($usuario->hasEnabledTwoFactorAuthentication() && ! $this->codigoDeDuasEtapasValido($usuario)) {
            throw ValidationException::withMessages([
                'codigo_2fa' => 'Esta conta usa autenticação em duas etapas. Informe o código de 6 dígitos do aplicativo autenticador.',
            ]);
        }

        return $usuario;
    }

    /**
     * Escopos pedidos para o token. Sem o campo, o token recebe todos.
     *
     * @return list<string>
     */
    public function escoposSolicitados(): array
    {
        $escopos = array_map('strval', (array) $this->validated('escopos', []));

        return $escopos === [] ? EscopoDaApi::valores() : array_values($escopos);
    }

    /**
     * Nome que identifica o token (ex.: "Postman", "App do celular").
     */
    public function nomeDoToken(): string
    {
        return $this->filled('nome_dispositivo')
            ? $this->string('nome_dispositivo')->toString()
            : 'API REST';
    }

    /**
     * Valida o código de 6 dígitos com o mesmo verificador usado no login web (Fortify).
     */
    private function codigoDeDuasEtapasValido(User $usuario): bool
    {
        $codigo = $this->string('codigo_2fa')->trim()->toString();

        return $codigo !== '' && app(TwoFactorAuthenticationProvider::class)->verify(
            Fortify::currentEncrypter()->decrypt((string) $usuario->two_factor_secret),
            $codigo,
        );
    }
}
