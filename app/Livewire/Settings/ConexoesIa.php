<?php

namespace App\Livewire\Settings;

use App\Models\User;
use Carbon\CarbonImmutable;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\Token;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Configurações → Conexões de IA: endereço do servidor MCP, como conectar
 * os apps de IA e a lista dos apps autorizados (com a opção de desconectar).
 */
#[Title('Conexões de IA')]
class ConexoesIa extends Component
{
    /**
     * Apps de IA conectados: um por cliente OAuth, com a data da conexão e do
     * último uso. Uma conexão continua ativa enquanto o token de acesso ou o
     * de renovação estiver válido.
     *
     * @return Collection<int, array{cliente: string, nome: string, conectado_em: CarbonImmutable|null, ultimo_uso: CarbonImmutable|null}>
     */
    #[Computed]
    public function conexoes(): Collection
    {
        return $this->tokensAtivos()
            ->with('client')
            ->latest()
            ->get()
            ->filter(fn (Token $token): bool => $token->client !== null)
            ->groupBy('client_id')
            ->map(function (Collection $tokens): array {
                /** @var Token $maisRecente */
                $maisRecente = $tokens->first();

                return [
                    'cliente' => (string) $maisRecente->client_id,
                    'nome' => (string) $maisRecente->client?->name,
                    'conectado_em' => $maisRecente->client?->created_at,
                    'ultimo_uso' => $maisRecente->created_at,
                ];
            })
            ->values();
    }

    /**
     * Revoga todos os tokens do app para este usuário: ele perde o acesso na hora.
     */
    public function desconectar(string $cliente): void
    {
        $this->usuario()->tokens()
            ->where('client_id', $cliente)
            ->get()
            ->each(function (Token $token): void {
                $token->revoke();
                $token->refreshToken?->revoke();
            });

        unset($this->conexoes);

        Flux::toast(variant: 'success', text: 'Aplicativo desconectado.');
    }

    /**
     * @return Builder<Token>
     */
    private function tokensAtivos(): Builder
    {
        return $this->usuario()->tokens()
            ->where('revoked', false)
            ->where(fn (Builder $query) => $query
                ->where('expires_at', '>', now())
                ->orWhereHas('refreshToken', fn (Builder $renovacao) => $renovacao
                    ->where('revoked', false)
                    ->where('expires_at', '>', now())))
            ->getQuery();
    }

    private function usuario(): User
    {
        /** @var User $usuario */
        $usuario = Auth::user();

        return $usuario;
    }
}
