<?php

namespace App\Services\Ia;

use Anthropic\Client;
use Anthropic\Core\Exceptions\APIConnectionException;
use Anthropic\Core\Exceptions\APIStatusException;
use Anthropic\Core\Exceptions\AuthenticationException;
use Anthropic\Core\Exceptions\PermissionDeniedException;
use Anthropic\Messages\AssistantMessageParam;
use Anthropic\Messages\Message;
use Anthropic\Messages\RedactedThinkingBlock;
use Anthropic\Messages\RedactedThinkingBlockParam;
use Anthropic\Messages\TextBlock;
use Anthropic\Messages\TextBlockParam;
use Anthropic\Messages\ThinkingBlock;
use Anthropic\Messages\ThinkingBlockParam;
use Anthropic\Messages\Tool;
use Anthropic\Messages\Tool\InputSchema;
use Anthropic\Messages\ToolChoiceAuto;
use Anthropic\Messages\ToolChoiceNone;
use Anthropic\Messages\ToolResultBlockParam;
use Anthropic\Messages\ToolUseBlock;
use Anthropic\Messages\ToolUseBlockParam;
use Anthropic\Messages\UserMessageParam;
use App\Contracts\AssistenteDeIa;
use App\Data\RespostaDoAssistente;
use App\Enums\PapelMensagem;
use App\Enums\ProvedorIa;
use App\Exceptions\AssistenteIndisponivel;
use App\Mcp\Tools\Ferramenta;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Psr\Http\Client\ClientInterface;

/**
 * Assistente com o Claude (Anthropic), pela API Messages com o SDK oficial.
 *
 * Custo: as definições das ferramentas e as instruções fixas vão para o cache
 * de prompt (cache_control), e o histórico da rodada atual também; o esforço
 * de raciocínio fica em "low", suficiente para consultas e cadastros.
 */
class AssistenteClaude implements AssistenteDeIa
{
    /**
     * @param  ClientInterface|null  $transporte  cliente HTTP (PSR-18); nos testes, um cliente falso
     */
    public function __construct(
        private FerramentasDoAssistente $ferramentas,
        private InstrucoesDoAssistente $instrucoes,
        private ?ClientInterface $transporte = null,
    ) {}

    public function provedor(): ProvedorIa
    {
        return ProvedorIa::Claude;
    }

    public function configurado(): bool
    {
        return filled(config('services.anthropic.api_key'));
    }

    public function modelo(): string
    {
        return (string) config('services.anthropic.model');
    }

    public function responder(User $usuario, array $historico): RespostaDoAssistente
    {
        if (! $this->configurado()) {
            throw AssistenteIndisponivel::semChave($this->provedor());
        }

        $cliente = $this->cliente();
        $ferramentas = $this->ferramentas->disponiveis($usuario);
        $definicoes = $this->definicoes($ferramentas);
        $mensagens = array_map(fn (array $mensagem): UserMessageParam|AssistantMessageParam => $mensagem['papel'] === PapelMensagem::Usuario
            ? UserMessageParam::with(content: $mensagem['conteudo'])
            : AssistantMessageParam::with(content: $mensagem['conteudo']), $historico);

        $tokens = ['entrada' => 0, 'saida' => 0, 'cache' => 0];
        $ferramentasUsadas = [];
        $ultimaRodada = max(1, (int) config('assistente.rodadas_de_ferramentas'));

        for ($rodada = 1; $rodada <= $ultimaRodada; $rodada++) {
            // Na última rodada a IA não pode mais chamar ferramentas: precisa responder em texto.
            $resposta = $this->enviar($cliente, $usuario, $mensagens, $definicoes, podeUsarFerramentas: $rodada < $ultimaRodada);
            $this->somarTokens($tokens, $resposta);

            if ($resposta->stopReason === 'refusal') {
                throw AssistenteIndisponivel::recusada();
            }

            if ($resposta->stopReason !== 'tool_use') {
                $texto = $this->textoDa($resposta);

                if ($texto === '') {
                    throw AssistenteIndisponivel::respostaInvalida();
                }

                return new RespostaDoAssistente(
                    conteudo: $texto,
                    modelo: $resposta->model,
                    tokensDeEntrada: $tokens['entrada'],
                    tokensDeSaida: $tokens['saida'],
                    tokensEmCache: $tokens['cache'],
                    ferramentasUsadas: $ferramentasUsadas,
                );
            }

            // Executa todas as ferramentas pedidas e devolve os resultados numa única mensagem.
            $resultados = [];

            foreach ($resposta->content as $bloco) {
                if ($bloco instanceof ToolUseBlock) {
                    $resultado = $this->ferramentas->executar($ferramentas, $bloco->name, $bloco->input);
                    $ferramentasUsadas[] = $bloco->name;
                    $resultados[] = ToolResultBlockParam::with(
                        toolUseID: $bloco->id,
                        content: $resultado['conteudo'],
                        isError: $resultado['erro'],
                    );
                }
            }

            // A resposta volta inteira (inclusive os blocos de raciocínio), como a API exige.
            $mensagens[] = AssistantMessageParam::with(content: $this->paraHistorico($resposta));
            $mensagens[] = UserMessageParam::with(content: $resultados);
        }

        throw AssistenteIndisponivel::respostaInvalida();
    }

    /**
     * Uma chamada à API Messages.
     *
     * Ordem do cache de prompt: ferramentas → instruções fixas (marcadas com
     * cache_control) → contexto do dia → mensagens (cache automático no fim).
     *
     * @param  list<UserMessageParam|AssistantMessageParam>  $mensagens
     * @param  list<Tool>  $definicoes
     */
    private function enviar(Client $cliente, User $usuario, array $mensagens, array $definicoes, bool $podeUsarFerramentas): Message
    {
        $esforco = config('services.anthropic.effort');

        try {
            return $cliente->messages->create(
                maxTokens: (int) config('services.anthropic.max_tokens'),
                messages: $mensagens,
                model: $this->modelo(),
                cacheControl: ['type' => 'ephemeral'],
                metadata: ['userID' => hash('sha256', 'usuario-'.$usuario->id)],
                outputConfig: filled($esforco) ? ['effort' => $esforco] : null,
                system: [
                    ['type' => 'text', 'text' => $this->instrucoes->fixas(), 'cacheControl' => ['type' => 'ephemeral']],
                    ['type' => 'text', 'text' => $this->instrucoes->contexto($usuario)],
                ],
                toolChoice: $podeUsarFerramentas ? ToolChoiceAuto::with() : ToolChoiceNone::with(),
                tools: $definicoes === [] ? null : $definicoes,
            );
        } catch (AuthenticationException|PermissionDeniedException $erro) {
            $this->registrarFalha($erro);

            throw AssistenteIndisponivel::chaveInvalida($this->provedor());
        } catch (APIStatusException|APIConnectionException $erro) {
            // Limite de requisições (429) e erros do servidor (5xx) já foram repetidos pelo SDK.
            $this->registrarFalha($erro);

            throw AssistenteIndisponivel::foraDoAr($this->provedor());
        }
    }

    /**
     * Ferramentas no formato da API do Claude.
     *
     * @param  Collection<string, Ferramenta>  $ferramentas
     * @return list<Tool>
     */
    private function definicoes(Collection $ferramentas): array
    {
        return array_map(function (array $definicao): Tool {
            $propriedades = (array) ($definicao['parametros']['properties'] ?? []);
            $obrigatorios = $definicao['parametros']['required'] ?? null;

            return Tool::with(
                inputSchema: InputSchema::with(
                    properties: $propriedades === [] ? null : $propriedades,
                    required: is_array($obrigatorios) ? array_values(array_map('strval', $obrigatorios)) : null,
                ),
                name: $definicao['nome'],
                description: $definicao['descricao'],
            );
        }, $this->ferramentas->definicoes($ferramentas));
    }

    /**
     * Converte os blocos da resposta para os blocos aceitos no histórico.
     * Os de raciocínio voltam sem alteração (com a assinatura), como a API exige.
     *
     * @return list<TextBlockParam|ThinkingBlockParam|RedactedThinkingBlockParam|ToolUseBlockParam>
     */
    private function paraHistorico(Message $resposta): array
    {
        $blocos = [];

        foreach ($resposta->content as $bloco) {
            if ($bloco instanceof ThinkingBlock) {
                $blocos[] = ThinkingBlockParam::with(signature: $bloco->signature, thinking: $bloco->thinking);
            } elseif ($bloco instanceof RedactedThinkingBlock) {
                $blocos[] = RedactedThinkingBlockParam::with(data: $bloco->data);
            } elseif ($bloco instanceof TextBlock && trim($bloco->text) !== '') {
                $blocos[] = TextBlockParam::with(text: $bloco->text);
            } elseif ($bloco instanceof ToolUseBlock) {
                $blocos[] = ToolUseBlockParam::with(id: $bloco->id, input: $bloco->input, name: $bloco->name);
            }
        }

        return $blocos;
    }

    private function cliente(): Client
    {
        return new Client(
            apiKey: (string) config('services.anthropic.api_key'),
            requestOptions: array_filter([
                'timeout' => (float) config('services.anthropic.timeout'),
                'maxRetries' => (int) config('services.anthropic.max_retries'),
                'transporter' => $this->transporte,
            ], fn (mixed $valor): bool => $valor !== null),
        );
    }

    private function textoDa(Message $resposta): string
    {
        $partes = [];

        foreach ($resposta->content as $bloco) {
            if ($bloco instanceof TextBlock && trim($bloco->text) !== '') {
                $partes[] = trim($bloco->text);
            }
        }

        return implode("\n\n", $partes);
    }

    /**
     * Soma os tokens da chamada. No Claude, input_tokens conta só a parte que
     * não veio do cache; a entrada total inclui as partes lidas e gravadas no cache.
     *
     * @param  array{entrada: int, saida: int, cache: int}  $tokens
     */
    private function somarTokens(array &$tokens, Message $resposta): void
    {
        $lidosDoCache = $resposta->usage->cacheReadInputTokens ?? 0;

        $tokens['entrada'] += $resposta->usage->inputTokens + $lidosDoCache + ($resposta->usage->cacheCreationInputTokens ?? 0);
        $tokens['saida'] += $resposta->usage->outputTokens;
        $tokens['cache'] += $lidosDoCache;
    }

    private function registrarFalha(\Throwable $erro): void
    {
        Log::error('Falha na chamada à API do Claude.', [
            'erro' => $erro::class,
            'status' => $erro instanceof APIStatusException ? $erro->status : null,
            'mensagem' => $erro->getMessage(),
        ]);
    }
}
