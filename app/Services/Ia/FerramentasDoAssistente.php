<?php

namespace App\Services\Ia;

use App\Mcp\Servers\SistemaAgricolaServer;
use App\Mcp\Tools\Ferramenta;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Laravel\Mcp\Request as RequisicaoMcp;
use Throwable;

/**
 * Ponte entre o chat e as ferramentas do servidor MCP.
 *
 * O chat do sistema não precisa passar pela rede para usar as ferramentas:
 * elas são as mesmas classes registradas no SistemaAgricolaServer e rodam
 * aqui mesmo, para o usuário logado. Assim existe um único lugar com as
 * regras de cada ação, seja ela pedida pelo chat ou por um app externo.
 */
class FerramentasDoAssistente
{
    /**
     * Ferramentas disponíveis, indexadas pelo nome (em ordem fixa).
     *
     * As ferramentas leem o usuário da autenticação, então só são oferecidas
     * quando a resposta é para quem está logado.
     *
     * @return Collection<string, Ferramenta>
     */
    public function disponiveis(User $usuario): Collection
    {
        if (! $usuario->is(auth()->user())) {
            return collect();
        }

        return collect(SistemaAgricolaServer::FERRAMENTAS)
            ->map(fn (string $classe): Ferramenta => app($classe))
            ->mapWithKeys(fn (Ferramenta $ferramenta): array => [$ferramenta->name() => $ferramenta]);
    }

    /**
     * Título legível de cada ferramenta (ex.: consultar-custos → Consultar custos),
     * exibido nas respostas para mostrar o que a IA consultou ou cadastrou.
     *
     * @return array<string, string>
     */
    public static function titulos(): array
    {
        static $titulos = null;

        return $titulos ??= collect(SistemaAgricolaServer::FERRAMENTAS)
            ->map(fn (string $classe): Ferramenta => app($classe))
            ->mapWithKeys(fn (Ferramenta $ferramenta): array => [$ferramenta->name() => $ferramenta->title()])
            ->all();
    }

    /**
     * Definições das ferramentas em formato neutro: nome, descrição e o
     * JSON Schema dos argumentos (cada provedor adapta para a sua API).
     *
     * @param  Collection<string, Ferramenta>  $ferramentas
     * @return list<array{nome: string, descricao: string, parametros: array<string, mixed>}>
     */
    public function definicoes(Collection $ferramentas): array
    {
        return array_values($ferramentas->map(fn (Ferramenta $ferramenta): array => [
            'nome' => $ferramenta->name(),
            'descricao' => $ferramenta->description(),
            'parametros' => $ferramenta->toArray()['inputSchema'] ?? ['type' => 'object', 'properties' => (object) []],
        ])->all());
    }

    /**
     * Executa a ferramenta pedida pela IA. O resultado volta como texto para a
     * IA, inclusive os erros (para que ela explique ou corrija o pedido).
     *
     * A IA pode citar uma ferramenta que não recebeu, por isso o nome é
     * conferido de novo aqui.
     *
     * @param  Collection<string, Ferramenta>  $ferramentas
     * @param  array<string, mixed>  $argumentos
     * @return array{conteudo: string, erro: bool}
     */
    public function executar(Collection $ferramentas, string $nome, array $argumentos): array
    {
        $ferramenta = $ferramentas->get($nome);

        if (! $ferramenta instanceof Ferramenta) {
            return ['conteudo' => "A ferramenta {$nome} não está disponível.", 'erro' => true];
        }

        try {
            $resposta = $ferramenta->handle(new RequisicaoMcp($argumentos));

            return ['conteudo' => (string) $resposta->content(), 'erro' => $resposta->isError()];
        } catch (Throwable $erro) {
            Log::error('Falha ao executar ferramenta do assistente de IA.', [
                'ferramenta' => $nome,
                'erro' => $erro::class,
                'mensagem' => $erro->getMessage(),
            ]);

            return ['conteudo' => 'Não foi possível executar a ferramenta. Avise o usuário e não tente de novo.', 'erro' => true];
        }
    }
}
