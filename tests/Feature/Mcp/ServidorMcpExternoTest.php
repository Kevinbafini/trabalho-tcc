<?php

use App\Livewire\Settings\ConexoesIa;
use App\Mcp\Servers\SistemaAgricolaServer;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Passport\Client;
use Laravel\Passport\Passport;
use Laravel\Passport\Token;
use Livewire\Livewire;

/*
 * Servidor MCP para apps de IA externos (Claude, ChatGPT...), com OAuth:
 * descoberta, bloqueio sem token, acesso só aos dados do usuário do token,
 * tela de autorização e a tela de conexões nas configurações.
 */

/**
 * Chamada JSON-RPC ao servidor MCP.
 *
 * @param  array<string, mixed>  $parametros
 * @return array<string, mixed>
 */
function chamadaMcp(string $metodo, array $parametros = []): array
{
    return ['jsonrpc' => '2.0', 'id' => 1, 'method' => $metodo, 'params' => (object) $parametros];
}

test('a descoberta do OAuth anuncia o servidor de autorização e o escopo do MCP', function () {
    $this->getJson('/.well-known/oauth-protected-resource')
        ->assertOk()
        ->assertJsonPath('authorization_servers.0', url('/'));

    $metadados = $this->getJson('/.well-known/oauth-authorization-server')->assertOk();

    expect($metadados->json('authorization_endpoint'))->toBe(route('passport.authorizations.authorize'))
        ->and($metadados->json('token_endpoint'))->toBe(route('passport.token'))
        ->and($metadados->json('registration_endpoint'))->toBe(url('oauth/register'))
        ->and($metadados->json('scopes_supported'))->toContain('mcp:use')
        ->and($metadados->json('code_challenge_methods_supported'))->toContain('S256');
});

test('sem token o servidor MCP recusa o acesso', function () {
    $this->postJson(route('mcp'), chamadaMcp('tools/list'))->assertUnauthorized();
});

test('com o token OAuth o app lista as ferramentas e vê só os dados do próprio usuário', function () {
    $cenario = criarPropriedadeComRecursos();
    $outro = criarPropriedadeComRecursos();
    Passport::actingAs($cenario['usuario'], ['mcp:use']);

    $ferramentas = $this->postJson(route('mcp'), chamadaMcp('tools/list'))->assertOk()->json('result.tools');

    expect(array_column($ferramentas, 'name'))->toHaveCount(count(SistemaAgricolaServer::FERRAMENTAS))
        ->toContain('registrar-atividade', 'consultar-custos', 'buscar-cadastros');

    $this->postJson(route('mcp'), chamadaMcp('tools/call', ['name' => 'resumo-geral', 'arguments' => (object) []]))
        ->assertOk()
        ->assertSee($cenario['propriedade']->nome)
        ->assertDontSee($outro['propriedade']->nome);
});

test('a tela de autorização mostra o app, a conta e o que ele poderá fazer, em português', function () {
    $usuario = User::factory()->create(['name' => 'Ana Souza']);
    $cliente = Client::factory()->asPublic()->create([
        'name' => 'Claude',
        'redirect_uris' => ['https://claude.ai/api/mcp/auth_callback'],
    ]);

    $this->actingAs($usuario)
        ->get(route('passport.authorizations.authorize', [
            'client_id' => $cliente->id,
            'redirect_uri' => 'https://claude.ai/api/mcp/auth_callback',
            'response_type' => 'code',
            'scope' => 'mcp:use',
            'state' => Str::random(16),
            'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', Str::random(64), true)), '+/', '-_'), '='),
            'code_challenge_method' => 'S256',
        ]))
        ->assertOk()
        ->assertSee(['Claude quer acessar o sistema como você', 'Ana Souza', 'claude.ai', 'Registrar atividade agrícola', 'Consultar custos', 'Autorizar', 'Cancelar']);
});

test('a tela de conexões mostra o endereço do MCP e desconecta um app na hora', function () {
    $usuario = User::factory()->create();
    $cliente = Client::factory()->asPublic()->create(['name' => 'ChatGPT']);
    $token = Token::query()->forceCreate([
        'id' => Str::random(80),
        'user_id' => $usuario->id,
        'client_id' => $cliente->id,
        'scopes' => ['mcp:use'],
        'revoked' => false,
        'expires_at' => now()->addHour(),
    ]);

    $this->actingAs($usuario)
        ->get(route('conexoes-ia.edit'))
        ->assertOk()
        ->assertSee([route('mcp'), 'claude mcp add --transport http agricola', 'ChatGPT']);

    Livewire::actingAs($usuario)
        ->test(ConexoesIa::class)
        ->call('desconectar', $cliente->id)
        ->assertSee('Nenhum aplicativo conectado.');

    expect($token->fresh()->revoked)->toBeTrue();
});

test('um usuário não desconecta o app de outro usuário', function () {
    $dono = User::factory()->create();
    $cliente = Client::factory()->asPublic()->create(['name' => 'ChatGPT']);
    $token = Token::query()->forceCreate([
        'id' => Str::random(80),
        'user_id' => $dono->id,
        'client_id' => $cliente->id,
        'scopes' => ['mcp:use'],
        'revoked' => false,
        'expires_at' => now()->addHour(),
    ]);

    Livewire::actingAs(User::factory()->create())
        ->test(ConexoesIa::class)
        ->call('desconectar', $cliente->id);

    expect($token->fresh()->revoked)->toBeFalse();
});
