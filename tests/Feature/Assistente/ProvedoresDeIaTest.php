<?php

use App\Enums\PapelMensagem;
use App\Exceptions\AssistenteIndisponivel;
use App\Models\AtividadeAgricola;
use App\Services\Ia\AssistenteClaude;
use App\Services\Ia\AssistenteOpenAi;
use App\Services\Ia\FerramentasDoAssistente;
use App\Services\Ia\InstrucoesDoAssistente;
use GuzzleHttp\Client as ClienteGuzzle;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response as RespostaHttp;
use Illuminate\Http\Client\Request as RequisicaoHttp;
use Illuminate\Support\Facades\Http;

/*
 * Integração com as APIs do Claude e da OpenAI, sem chamá-las de verdade:
 * as respostas da API são simuladas e as ferramentas rodam de verdade no
 * banco de testes.
 */

beforeEach(function () {
    config([
        'services.anthropic.api_key' => 'chave-de-teste',
        'services.anthropic.max_retries' => 0,
        'services.openai.api_key' => 'chave-de-teste',
    ]);

    $this->cenario = criarPropriedadeComRecursos();
    $this->actingAs($this->cenario['usuario']);
    $this->historico = [['papel' => PapelMensagem::Usuario, 'conteudo' => 'Quanto custaria a adubação do Talhão 01?']];
});

/**
 * Resposta da API Messages do Claude.
 *
 * @param  list<array<string, mixed>>  $conteudo
 */
function respostaDoClaude(array $conteudo, string $motivoDaParada, int $entrada = 100, int $saida = 20, int $cache = 0): RespostaHttp
{
    return new RespostaHttp(200, ['Content-Type' => 'application/json'], json_encode([
        'id' => 'msg_'.uniqid(),
        'type' => 'message',
        'role' => 'assistant',
        'model' => 'claude-haiku-5-5',
        'content' => $conteudo,
        'stop_reason' => $motivoDaParada,
        'stop_sequence' => null,
        'usage' => ['input_tokens' => $entrada, 'output_tokens' => $saida, 'cache_read_input_tokens' => $cache, 'cache_creation_input_tokens' => 0],
    ]));
}

/**
 * Assistente do Claude ligado a um cliente HTTP falso, que guarda as requisições enviadas.
 *
 * @param  list<RespostaHttp>  $respostas
 * @param  array<int, array<string, mixed>>  $enviadas
 */
function claudeSimulado(array $respostas, array &$enviadas): AssistenteClaude
{
    $pilha = HandlerStack::create(new MockHandler($respostas));
    $pilha->push(Middleware::history($enviadas));

    return new AssistenteClaude(app(FerramentasDoAssistente::class), app(InstrucoesDoAssistente::class), new ClienteGuzzle(['handler' => $pilha]));
}

/**
 * Corpo JSON da n-ésima requisição enviada ao Claude.
 *
 * @param  array<int, array<string, mixed>>  $enviadas
 * @return array<string, mixed>
 */
function corpoEnviado(array $enviadas, int $indice): array
{
    return json_decode((string) $enviadas[$indice]['request']->getBody(), true);
}

test('o Claude executa a ferramenta pedida e responde com base no resultado', function () {
    $enviadas = [];
    $argumentos = [...dadosDaAtividade($this->cenario), 'confirmado_pelo_usuario' => false];

    $resposta = claudeSimulado([
        respostaDoClaude([['type' => 'tool_use', 'id' => 'toolu_1', 'name' => 'registrar-atividade', 'input' => $argumentos]], 'tool_use', cache: 40),
        respostaDoClaude([['type' => 'text', 'text' => 'A adubação custaria **R$ 1.335,50**. Posso salvar?']], 'end_turn', entrada: 300, saida: 30),
    ], $enviadas)->responder($this->cenario['usuario'], $this->historico);

    expect($resposta->conteudo)->toBe('A adubação custaria **R$ 1.335,50**. Posso salvar?')
        ->and($resposta->ferramentasUsadas)->toBe(['registrar-atividade'])
        ->and($resposta->tokensDeEntrada)->toBe(440) // 100 + 40 lidos do cache + 300
        ->and($resposta->tokensDeSaida)->toBe(50)
        ->and($resposta->tokensEmCache)->toBe(40)
        ->and(AtividadeAgricola::count())->toBe(0); // era só a prévia

    $primeira = corpoEnviado($enviadas, 0);
    $segunda = corpoEnviado($enviadas, 1);
    $resultado = collect($segunda['messages'][2]['content'])->firstWhere('type', 'tool_result');

    expect($primeira['model'])->toBe('claude-haiku-5-5')
        ->and($primeira['output_config'])->toBe(['effort' => 'low'])
        ->and($primeira['tool_choice'])->toBe(['type' => 'auto'])
        ->and($primeira['system'][0]['cache_control'])->toBe(['type' => 'ephemeral'])
        ->and($primeira['system'][1]['text'])->toContain(now()->format('d/m/Y'))
        ->and(array_column($primeira['tools'], 'name'))->toContain('registrar-atividade', 'buscar-cadastros', 'consultar-custos')
        ->and($primeira['metadata']['user_id'])->toBe(hash('sha256', 'usuario-'.$this->cenario['usuario']->id)) // nunca o e-mail ou o ID puro
        ->and($resultado['tool_use_id'])->toBe('toolu_1')
        ->and($resultado['content'])->toContain('R$ 1.335,50');
});

test('na última rodada o Claude não pode mais chamar ferramentas e precisa responder', function () {
    config(['assistente.rodadas_de_ferramentas' => 1]);
    $enviadas = [];

    claudeSimulado([respostaDoClaude([['type' => 'text', 'text' => 'Resposta direta.']], 'end_turn')], $enviadas)
        ->responder($this->cenario['usuario'], $this->historico);

    expect(corpoEnviado($enviadas, 0)['tool_choice'])->toBe(['type' => 'none']);
});

test('as falhas do Claude viram mensagens amigáveis', function (int $status, string $mensagem) {
    $enviadas = [];

    expect(fn () => claudeSimulado([new RespostaHttp($status, ['Content-Type' => 'application/json'], '{"type":"error","error":{"type":"x","message":"x"}}')], $enviadas)
        ->responder($this->cenario['usuario'], $this->historico))
        ->toThrow(AssistenteIndisponivel::class, $mensagem);
})->with([
    'chave recusada' => [401, 'A chave da API do Claude foi recusada.'],
    'servidor fora do ar' => [529, 'O Claude está temporariamente indisponível.'],
]);

test('sem a chave configurada o provedor avisa e não chama a API', function () {
    config(['services.anthropic.api_key' => null]);
    $enviadas = [];

    expect(fn () => claudeSimulado([], $enviadas)->responder($this->cenario['usuario'], $this->historico))
        ->toThrow(AssistenteIndisponivel::class, 'O Claude ainda não está configurado')
        ->and($enviadas)->toBe([]);
});

test('a OpenAI executa a ferramenta pedida e devolve o raciocínio criptografado entre as rodadas', function () {
    Http::fake(['api.openai.com/*' => Http::sequence()
        ->push([
            'model' => 'gpt-6-luna',
            'output' => [
                ['type' => 'reasoning', 'id' => 'rs_1', 'encrypted_content' => 'cifrado', 'summary' => []],
                ['type' => 'function_call', 'call_id' => 'call_1', 'name' => 'consultar-custos', 'arguments' => '{"safra":"2025/2026"}'],
            ],
            'usage' => ['input_tokens' => 200, 'output_tokens' => 15, 'input_tokens_details' => ['cached_tokens' => 120]],
        ])
        ->push([
            'model' => 'gpt-6-luna',
            'output' => [['type' => 'message', 'content' => [['type' => 'output_text', 'text' => 'Ainda não há custos nesta safra.']]]],
            'usage' => ['input_tokens' => 260, 'output_tokens' => 10],
        ]),
    ]);

    $resposta = app(AssistenteOpenAi::class)->responder($this->cenario['usuario'], $this->historico);

    expect($resposta->conteudo)->toBe('Ainda não há custos nesta safra.')
        ->and($resposta->ferramentasUsadas)->toBe(['consultar-custos'])
        ->and($resposta->tokensDeEntrada)->toBe(460)
        ->and($resposta->tokensEmCache)->toBe(120);

    $requisicoes = Http::recorded()->map(fn (array $par): RequisicaoHttp => $par[0]);
    $segunda = $requisicoes[1]->data();

    expect($requisicoes[0]->data())->toMatchArray(['model' => 'gpt-6-luna', 'store' => false, 'include' => ['reasoning.encrypted_content'], 'reasoning' => ['effort' => 'low'], 'tool_choice' => 'auto'])
        ->and(collect($segunda['input'])->firstWhere('type', 'reasoning')['encrypted_content'])->toBe('cifrado')
        ->and(collect($segunda['input'])->firstWhere('type', 'function_call_output'))->toMatchArray(['call_id' => 'call_1'])
        ->and(collect($segunda['input'])->firstWhere('type', 'function_call_output')['output'])->toContain('"atividades":0');
});

test('a OpenAI com a chave recusada vira uma mensagem amigável', function () {
    Http::fake(['api.openai.com/*' => Http::response(['error' => ['message' => 'invalid key']], 401)]);

    expect(fn () => app(AssistenteOpenAi::class)->responder($this->cenario['usuario'], $this->historico))
        ->toThrow(AssistenteIndisponivel::class, 'A chave da API do OpenAI foi recusada.');
});
