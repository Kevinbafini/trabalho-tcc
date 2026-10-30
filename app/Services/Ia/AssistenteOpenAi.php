<?php

namespace App\Services\Ia;

use App\Contracts\AssistenteDeIa;
use App\Data\RespostaDoAssistente;
use App\Enums\ProvedorIa;
use App\Exceptions\AssistenteIndisponivel;
use App\Mcp\Tools\Ferramenta;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Assistente com a OpenAI, pela Responses API (cliente HTTP do Laravel).
 *
 * Nada fica guardado na OpenAI (store=false): o histórico é enviado a cada
 * chamada. Por isso o raciocínio do modelo volta criptografado
 * (reasoning.encrypted_content) e é devolvido junto nas rodadas de ferramentas.
 * O cache de prompt da OpenAI é automático; o prompt_cache_key por usuário
 * ajuda a reaproveitá-lo.
 */
class AssistenteOpenAi implements AssistenteDeIa
{
    public function __construct(
        private FerramentasDoAssistente $ferramentas,
        private InstrucoesDoAssistente $instrucoes,
    ) {}

    public function provedor(): ProvedorIa
    {
        return ProvedorIa::OpenAi;
    }

    public function configurado(): bool
    {
        return filled(config('services.openai.api_key'));
    }

    public function modelo(): string
    {
        return (string) config('services.openai.model');
    }

    public function responder(User $usuario, array $historico): RespostaDoAssistente
    {
        if (! $this->configurado()) {
            throw AssistenteIndisponivel::semChave($this->provedor());
        }

        $ferramentas = $this->ferramentas->disponiveis($usuario);
        $definicoes = $this->definicoes($ferramentas);
        $entrada = array_map(fn (array $mensagem): array => [
            'role' => $mensagem['papel']->value,
            'content' => $mensagem['conteudo'],
        ], $historico);

        $tokens = ['entrada' => 0, 'saida' => 0, 'cache' => 0];
        $ferramentasUsadas = [];
        $ultimaRodada = max(1, (int) config('assistente.rodadas_de_ferramentas'));

        for ($rodada = 1; $rodada <= $ultimaRodada; $rodada++) {
            $dados = $this->enviar($usuario, $entrada, $definicoes, podeUsarFerramentas: $rodada < $ultimaRodada);
            $this->somarTokens($tokens, $dados['usage'] ?? null);

            $saida = array_values(array_filter((array) ($dados['output'] ?? []), 'is_array'));
            $chamadas = array_values(array_filter($saida, fn (array $item): bool => ($item['type'] ?? null) === 'function_call'));

            if ($chamadas === []) {
                $texto = $this->textoDa($saida);

                if ($texto === '') {
                    throw AssistenteIndisponivel::respostaInvalida();
                }

                return new RespostaDoAssistente(
                    conteudo: $texto,
                    modelo: is_string($dados['model'] ?? null) ? $dados['model'] : $this->modelo(),
                    tokensDeEntrada: $tokens['entrada'],
                    tokensDeSaida: $tokens['saida'],
                    tokensEmCache: $tokens['cache'],
                    ferramentasUsadas: $ferramentasUsadas,
                );
            }

            // A saída volta inteira para a próxima chamada (raciocínio + chamadas), seguida dos resultados.
            $entrada = [...$entrada, ...$saida];

            foreach ($chamadas as $chamada) {
                $nome = (string) ($chamada['name'] ?? '');
                $argumentos = json_decode((string) ($chamada['arguments'] ?? '{}'), associative: true);
                $resultado = is_array($argumentos)
                    ? $this->ferramentas->executar($ferramentas, $nome, $argumentos)
                    : ['conteudo' => 'Os argumentos da ferramenta não são um JSON válido.', 'erro' => true];

                $ferramentasUsadas[] = $nome;
                $entrada[] = [
                    'type' => 'function_call_output',
                    'call_id' => (string) ($chamada['call_id'] ?? ''),
                    'output' => $resultado['conteudo'],
                ];
            }
        }

        throw AssistenteIndisponivel::respostaInvalida();
    }

    /**
     * Uma chamada à Responses API.
     *
     * @param  list<array<string, mixed>>  $entrada
     * @param  list<array<string, mixed>>  $definicoes
     * @return array<string, mixed>
     */
    private function enviar(User $usuario, array $entrada, array $definicoes, bool $podeUsarFerramentas): array
    {
        $identificador = hash('sha256', 'usuario-'.$usuario->id);
        $esforco = config('services.openai.reasoning_effort');

        $corpo = array_filter([
            'model' => $this->modelo(),
            'instructions' => $this->instrucoes->fixas()."\n\n".$this->instrucoes->contexto($usuario),
            'input' => $entrada,
            'store' => false,
            'include' => ['reasoning.encrypted_content'],
            'max_output_tokens' => (int) config('services.openai.max_output_tokens'),
            'reasoning' => filled($esforco) ? ['effort' => $esforco] : null,
            'tools' => $definicoes === [] ? null : $definicoes,
            'tool_choice' => $definicoes === [] ? null : ($podeUsarFerramentas ? 'auto' : 'none'),
            'safety_identifier' => $identificador,
            'prompt_cache_key' => $identificador,
        ], fn (mixed $valor): bool => $valor !== null);

        try {
            $dados = Http::acceptJson()
                ->withToken((string) config('services.openai.api_key'))
                ->timeout((int) config('services.openai.timeout'))
                ->retry(
                    [500, 1500],
                    when: fn (Throwable $erro): bool => $erro instanceof ConnectionException
                        || ($erro instanceof RequestException && ($erro->response->serverError() || $erro->response->status() === 429)),
                )
                ->post((string) config('services.openai.responses_url'), $corpo)
                ->throw()
                ->json();
        } catch (RequestException $erro) {
            Log::error('Falha na chamada à API da OpenAI.', [
                'status' => $erro->response->status(),
                'request_id' => $erro->response->header('x-request-id'),
            ]);

            throw in_array($erro->response->status(), [401, 403], true)
                ? AssistenteIndisponivel::chaveInvalida($this->provedor())
                : AssistenteIndisponivel::foraDoAr($this->provedor());
        } catch (ConnectionException $erro) {
            Log::error('Falha de conexão com a API da OpenAI.', ['mensagem' => $erro->getMessage()]);

            throw AssistenteIndisponivel::foraDoAr($this->provedor());
        }

        if (! is_array($dados)) {
            throw AssistenteIndisponivel::respostaInvalida();
        }

        return $dados;
    }

    /**
     * Ferramentas no formato da Responses API (function calling).
     *
     * @param  Collection<string, Ferramenta>  $ferramentas
     * @return list<array<string, mixed>>
     */
    private function definicoes(Collection $ferramentas): array
    {
        return array_map(fn (array $definicao): array => [
            'type' => 'function',
            'name' => $definicao['nome'],
            'description' => $definicao['descricao'],
            'parameters' => $definicao['parametros'],
        ], $this->ferramentas->definicoes($ferramentas));
    }

    /**
     * Texto final escrito pelo modelo.
     *
     * @param  list<array<string, mixed>>  $saida
     */
    private function textoDa(array $saida): string
    {
        $partes = [];

        foreach ($saida as $item) {
            if (($item['type'] ?? null) !== 'message' || ! is_array($item['content'] ?? null)) {
                continue;
            }

            foreach ($item['content'] as $conteudo) {
                if (is_array($conteudo) && ($conteudo['type'] ?? null) === 'output_text' && is_string($conteudo['text'] ?? null) && trim($conteudo['text']) !== '') {
                    $partes[] = trim($conteudo['text']);
                }
            }
        }

        return implode("\n\n", $partes);
    }

    /**
     * @param  array{entrada: int, saida: int, cache: int}  $tokens
     */
    private function somarTokens(array &$tokens, mixed $uso): void
    {
        if (! is_array($uso)) {
            return;
        }

        $tokens['entrada'] += (int) ($uso['input_tokens'] ?? 0);
        $tokens['saida'] += (int) ($uso['output_tokens'] ?? 0);
        $tokens['cache'] += (int) data_get($uso, 'input_tokens_details.cached_tokens', 0);
    }
}
