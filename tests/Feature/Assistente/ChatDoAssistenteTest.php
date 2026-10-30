<?php

use App\Contracts\AssistenteDeIa;
use App\Data\RespostaDoAssistente;
use App\Enums\PapelMensagem;
use App\Enums\ProvedorIa;
use App\Exceptions\AssistenteIndisponivel;
use App\Models\Conversa;
use App\Models\Mensagem;
use App\Models\User;
use App\Services\Ia\AssistenteClaude;
use Illuminate\Support\Str;

/*
 * Chat do Assistente IA: tela, painel lateral, envio de mensagens, limites
 * de uso e isolamento entre usuários. O provedor é substituído por um
 * assistente falso, que registra o histórico recebido.
 */

/**
 * Assistente falso no lugar do Claude.
 */
function assistenteFalso(string|Throwable $resposta = 'Resposta da IA'): AssistenteDeIa
{
    $falso = new class($resposta) implements AssistenteDeIa
    {
        /** @var list<list<array{papel: PapelMensagem, conteudo: string}>> */
        public array $historicos = [];

        public function __construct(private string|Throwable $resposta) {}

        public function provedor(): ProvedorIa
        {
            return ProvedorIa::Claude;
        }

        public function configurado(): bool
        {
            return true;
        }

        public function modelo(): string
        {
            return 'modelo-de-teste';
        }

        public function responder(User $usuario, array $historico): RespostaDoAssistente
        {
            $this->historicos[] = $historico;

            if ($this->resposta instanceof Throwable) {
                throw $this->resposta;
            }

            return new RespostaDoAssistente($this->resposta, 'modelo-de-teste', 120, 30, 80, ['consultar-custos']);
        }
    };

    app()->instance(AssistenteClaude::class, $falso);

    return $falso;
}

beforeEach(function () {
    config(['services.anthropic.api_key' => null, 'services.openai.api_key' => null]);

    $this->usuario = User::factory()->create();
    $this->actingAs($this->usuario);
});

test('a tela do assistente abre com as sugestões e o seletor de provedor', function () {
    assistenteFalso();

    $this->get(route('assistente.index'))
        ->assertOk()
        ->assertSee(['Assistente IA', 'Como posso ajudar, '.Str::before($this->usuario->name, ' ').'?', 'Faça um resumo dos meus custos nesta safra.', 'Claude', 'OpenAI', '60 mensagens restantes hoje'], false)
        ->assertDontSee('Assistente ainda não configurado');
});

test('sem nenhuma chave de API a tela avisa que o assistente não está configurado', function () {
    $this->get(route('assistente.index'))
        ->assertOk()
        ->assertSee(['Assistente ainda não configurado', 'ANTHROPIC_API_KEY']);
});

test('enviar uma mensagem cria a conversa e grava a pergunta e a resposta com os tokens', function () {
    assistenteFalso('Você gastou **R$ 2.671,00** nesta safra.');

    $resposta = $this->postJson(route('assistente.mensagens.store'), ['mensagem' => '  Quanto gastei nesta safra?  ', 'provedor' => 'claude'])
        ->assertOk()
        ->assertJson(['erro' => null, 'restantes' => 59]);

    $conversa = Conversa::sole();
    $respostaDaIa = $conversa->mensagens->last();

    expect($resposta->json('conversa_id'))->toBe($conversa->id)
        ->and($resposta->json('mensagens'))->toContain('Quanto gastei nesta safra?', '<strong>R$ 2.671,00</strong>', 'Consultar custos', '150 tokens')
        ->and($conversa->titulo)->toBe('Quanto gastei nesta safra?')
        ->and($conversa->user_id)->toBe($this->usuario->id)
        ->and($conversa->mensagens->pluck('papel')->all())->toBe([PapelMensagem::Usuario, PapelMensagem::Assistente])
        ->and($respostaDaIa->provedor)->toBe(ProvedorIa::Claude)
        ->and($respostaDaIa->modelo)->toBe('modelo-de-teste')
        ->and($respostaDaIa->ferramentas)->toBe(['consultar-custos'])
        ->and([$respostaDaIa->tokens_entrada, $respostaDaIa->tokens_saida, $respostaDaIa->tokens_cache])->toBe([120, 30, 80]);
});

test('a conversa continua com o histórico das mensagens anteriores', function () {
    $falso = assistenteFalso('Certo.');

    $conversaId = $this->postJson(route('assistente.mensagens.store'), ['mensagem' => 'Primeira pergunta', 'provedor' => 'claude'])->json('conversa_id');
    $this->postJson(route('assistente.mensagens.store'), ['mensagem' => 'Segunda pergunta', 'provedor' => 'claude', 'conversa_id' => $conversaId])->assertOk();

    expect(Conversa::count())->toBe(1)
        ->and(array_column($falso->historicos[1], 'conteudo'))->toBe(['Primeira pergunta', 'Certo.', 'Segunda pergunta']);
});

test('só as mensagens mais recentes vão para a IA, sempre começando por uma do usuário', function () {
    config(['assistente.historico' => 2]);
    $falso = assistenteFalso('Ok.');

    $conversaId = $this->postJson(route('assistente.mensagens.store'), ['mensagem' => 'Antiga', 'provedor' => 'claude'])->json('conversa_id');
    $this->postJson(route('assistente.mensagens.store'), ['mensagem' => 'Nova', 'provedor' => 'claude', 'conversa_id' => $conversaId]);

    // As 2 últimas seriam "Ok." (da IA) e "Nova"; a da IA fica de fora para a conversa começar pelo usuário.
    expect(array_column($falso->historicos[1], 'conteudo'))->toBe(['Nova']);
});

test('a resposta da IA vira Markdown seguro, sem HTML e sem imagens', function () {
    assistenteFalso("**Pronto.** <script>alert('x')</script> ![dados](https://exemplo.com/x.png?d=segredo) [atividade](https://exemplo.com/atividades/1)");

    $html = $this->postJson(route('assistente.mensagens.store'), ['mensagem' => 'Oi', 'provedor' => 'claude'])->json('mensagens');

    expect($html)->toContain('<strong>Pronto.</strong>', 'dados', '<a href="https://exemplo.com/atividades/1">')
        ->not->toContain('<script', '<img', 'segredo');
});

test('quando a IA falha a pergunta fica gravada e o erro aparece no chat', function () {
    assistenteFalso(AssistenteIndisponivel::foraDoAr(ProvedorIa::Claude));

    $this->postJson(route('assistente.mensagens.store'), ['mensagem' => 'Quanto gastei?', 'provedor' => 'claude'])
        ->assertOk()
        ->assertJsonPath('erro', 'O Claude está temporariamente indisponível. Tente de novo em alguns instantes ou troque de provedor.');

    expect(Mensagem::query()->pluck('conteudo')->all())->toBe(['Quanto gastei?']);
});

test('os limites de uso bloqueiam o envio antes de chamar a IA', function (array $configuracao, string $erro) {
    config($configuracao);
    $falso = assistenteFalso();

    $this->postJson(route('assistente.mensagens.store'), ['mensagem' => 'Primeira', 'provedor' => 'claude'])->assertJsonPath('erro', null);
    $this->postJson(route('assistente.mensagens.store'), ['mensagem' => 'Segunda', 'provedor' => 'claude'])->assertJsonPath('erro', fn (string $mensagem) => str_contains($mensagem, $erro));

    expect($falso->historicos)->toHaveCount(1)
        ->and(Mensagem::query()->where('conteudo', 'Segunda')->exists())->toBeFalse();
})->with([
    'por dia' => [['assistente.mensagens_por_dia' => 1], 'limite de 1 mensagens por dia'],
    'por minuto' => [['assistente.mensagens_por_minuto' => 1], 'muitas mensagens seguidas'],
]);

test('provedor sem chave ou assistente desligado não chamam a IA', function (array $configuracao, string $provedor, string $erro) {
    config($configuracao);
    $falso = assistenteFalso();

    $this->postJson(route('assistente.mensagens.store'), ['mensagem' => 'Oi', 'provedor' => $provedor])
        ->assertOk()
        ->assertJsonPath('erro', fn (string $mensagem) => str_contains($mensagem, $erro));

    expect($falso->historicos)->toBe([])
        ->and(Conversa::count())->toBe(0);
})->with([
    'OpenAI sem chave' => [[], 'openai', 'O OpenAI ainda não está configurado'],
    'assistente desligado' => [['assistente.habilitado' => false], 'claude', 'O assistente de IA está desligado'],
]);

test('um usuário não vê, não continua e não exclui a conversa de outro', function () {
    assistenteFalso();
    $conversaDeOutro = Conversa::factory()->create(['titulo' => 'Conversa particular']);

    $this->get(route('assistente.index', ['conversa' => $conversaDeOutro->id]))->assertOk()->assertDontSee('Conversa particular');
    $this->get(route('assistente.painel', ['conversa' => $conversaDeOutro->id]))->assertOk()->assertDontSee('Conversa particular');

    $this->postJson(route('assistente.mensagens.store'), ['mensagem' => 'Oi', 'provedor' => 'claude', 'conversa_id' => $conversaDeOutro->id])
        ->assertJsonValidationErrors(['conversa_id' => 'O valor selecionado em conversa é inválido.']);

    $this->delete(route('assistente.conversas.destroy', $conversaDeOutro))->assertNotFound();

    expect($conversaDeOutro->fresh())->not->toBeNull();
});

test('excluir uma conversa remove as mensagens dela', function () {
    $conversa = Conversa::factory()->for($this->usuario)->create();
    Mensagem::factory()->for($conversa)->create();

    $this->delete(route('assistente.conversas.destroy', $conversa))
        ->assertRedirect(route('assistente.index'))
        ->assertSessionHas('sucesso', 'Conversa excluída.');

    expect(Conversa::count())->toBe(0)
        ->and(Mensagem::count())->toBe(0);
});

test('o painel lateral abre a conversa mais recente e permite começar uma nova', function () {
    assistenteFalso();
    Conversa::factory()->for($this->usuario)->create(['titulo' => 'Conversa antiga', 'ultima_mensagem_em' => now()->subDay()]);
    Conversa::factory()->for($this->usuario)->create(['titulo' => 'Conversa recente', 'ultima_mensagem_em' => now()]);

    $this->get(route('assistente.painel'))->assertOk()->assertSee('Conversa recente')->assertDontSee('Conversa antiga');
    $this->get(route('assistente.painel', ['nova' => 1]))->assertOk()->assertSee(['Nova conversa', 'Como posso ajudar?']);
});

test('sem JavaScript o envio redireciona para a conversa', function () {
    assistenteFalso();

    $resposta = $this->post(route('assistente.mensagens.store'), ['mensagem' => 'Oi', 'provedor' => 'claude']);

    $resposta->assertRedirect(route('assistente.index', ['conversa' => Conversa::sole()->id]));
});

test('o botão do assistente aparece nas outras telas, mas não na do próprio assistente', function () {
    $this->get(route('dashboard'))->assertOk()->assertSee('Abrir o Assistente IA');
    $this->get(route('assistente.index'))->assertOk()->assertDontSee('Abrir o Assistente IA');
});
