<?php

use App\Models\User;
use Carbon\CarbonImmutable;
use Laravel\Passport\Bridge\Client as EntidadeDoCliente;
use Laravel\Passport\Bridge\Scope;
use Laravel\Passport\Bridge\ScopeRepository;
use Laravel\Passport\Client;
use Laravel\Passport\Passport;
use PragmaRX\Google2FA\Google2FA;

/*
 * Autenticação da API REST: login com e-mail e senha (token pessoal do
 * Passport), uso do token, escopos, logout e as respostas 401 e 403.
 */

const MENSAGEM_NAO_AUTENTICADO = 'Não autenticado. Envie um token válido no cabeçalho Authorization: Bearer {token}.';

test('o login devolve um token Bearer com todos os escopos da API', function () {
    $usuario = User::factory()->create(['name' => 'Ana Souza', 'email' => 'ana@exemplo.com']);
    Client::factory()->asPersonalAccessTokenClient()->create();

    $resposta = $this->postJson(route('api.v1.login'), [
        'email' => 'ana@exemplo.com',
        'password' => 'password',
        'nome_dispositivo' => 'Postman',
    ]);

    $resposta->assertOk()
        ->assertJsonPath('data.tipo', 'Bearer')
        ->assertJsonPath('data.escopos', ['api:leitura', 'api:escrita'])
        ->assertJsonPath('data.usuario', ['id' => $usuario->id, 'nome' => 'Ana Souza', 'email' => 'ana@exemplo.com']);
    expect($resposta->json('data.token'))->toBeString()->not->toBeEmpty();
    $this->assertDatabaseHas('oauth_access_tokens', ['user_id' => $usuario->id, 'name' => 'Postman', 'revoked' => false]);
});

test('o token do login identifica o usuário nas requisições seguintes', function () {
    $usuario = User::factory()->create(['email' => 'ana@exemplo.com']);
    Client::factory()->asPersonalAccessTokenClient()->create();
    $token = $this->postJson(route('api.v1.login'), ['email' => 'ana@exemplo.com', 'password' => 'password'])->json('data.token');

    $resposta = $this->withToken($token)->getJson(route('api.v1.usuario'));

    $resposta->assertOk()->assertJsonPath('data.id', $usuario->id);
});

test('o token da API expira em 7 dias', function () {
    User::factory()->create(['email' => 'ana@exemplo.com']);
    Client::factory()->asPersonalAccessTokenClient()->create();

    $expiraEm = $this->postJson(route('api.v1.login'), ['email' => 'ana@exemplo.com', 'password' => 'password'])
        ->assertOk()
        ->json('data.expira_em');

    expect(CarbonImmutable::parse($expiraEm)->diffInMinutes(now()->addDays(7), absolute: true))->toBeLessThan(1);
});

test('recusa e-mail ou senha incorretos sem criar token', function (string $email, string $senha) {
    User::factory()->create(['email' => 'ana@exemplo.com']);

    $resposta = $this->postJson(route('api.v1.login'), ['email' => $email, 'password' => $senha]);

    $resposta->assertUnprocessable()
        ->assertJsonPath('message', 'Os dados enviados são inválidos.')
        ->assertJsonValidationErrors(['email' => 'E-mail ou senha incorretos.']);
    $this->assertDatabaseCount('oauth_access_tokens', 0);
})->with([
    'senha errada' => ['ana@exemplo.com', 'senha-errada'],
    'e-mail não cadastrado' => ['ninguem@exemplo.com', 'password'],
]);

test('exige e-mail e senha no login', function () {
    $this->postJson(route('api.v1.login'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'email' => 'O campo e-mail é obrigatório.',
            'password' => 'O campo senha é obrigatório.',
        ]);
});

test('não aceita pedir no login um escopo que não é da API', function () {
    User::factory()->create(['email' => 'ana@exemplo.com']);

    $this->postJson(route('api.v1.login'), [
        'email' => 'ana@exemplo.com',
        'password' => 'password',
        'escopos' => ['mcp:use'],
    ])->assertJsonValidationErrors(['escopos.0' => 'Escopo inválido. Use api:leitura e/ou api:escrita.']);

    $this->assertDatabaseCount('oauth_access_tokens', 0);
});

test('conta com autenticação em duas etapas não entra sem o código do aplicativo', function () {
    User::factory()->withTwoFactor()->create(['email' => 'ana@exemplo.com']);

    $this->postJson(route('api.v1.login'), ['email' => 'ana@exemplo.com', 'password' => 'password'])
        ->assertJsonValidationErrors(['codigo_2fa' => 'Esta conta usa autenticação em duas etapas. Informe o código de 6 dígitos do aplicativo autenticador.']);

    $this->assertDatabaseCount('oauth_access_tokens', 0);
});

test('conta com autenticação em duas etapas entra com o código correto do aplicativo', function () {
    $google2fa = app(Google2FA::class);
    $segredo = $google2fa->generateSecretKey();
    User::factory()->withTwoFactor()->create(['email' => 'ana@exemplo.com', 'two_factor_secret' => encrypt($segredo)]);
    Client::factory()->asPersonalAccessTokenClient()->create();

    $this->postJson(route('api.v1.login'), [
        'email' => 'ana@exemplo.com',
        'password' => 'password',
        'codigo_2fa' => $google2fa->getCurrentOtp($segredo),
    ])->assertOk();
});

test('um token só de leitura consulta, mas recebe 403 ao tentar cadastrar', function () {
    $cenario = criarPropriedadeComRecursos();
    Client::factory()->asPersonalAccessTokenClient()->create();
    $token = $this->postJson(route('api.v1.login'), [
        'email' => $cenario['usuario']->email,
        'password' => 'password',
        'escopos' => ['api:leitura'],
    ])->assertJsonPath('data.escopos', ['api:leitura'])->json('data.token');

    $this->withToken($token)->getJson(route('api.v1.atividades.index'))->assertOk();
    $this->withToken($token)->postJson(route('api.v1.atividades.store'), dadosDaAtividade($cenario))
        ->assertForbidden()
        ->assertExactJson(['message' => 'Este token não tem permissão para esta operação. Escopo necessário: api:escrita.']);

    $this->assertDatabaseCount('atividades_agricolas', 0);
});

test('sem o escopo de escrita responde 403 antes de procurar o registro', function () {
    Passport::actingAs(User::factory()->create(), ['api:leitura']);

    $this->deleteJson('/api/v1/atividades/999999')
        ->assertForbidden()
        ->assertExactJson(['message' => 'Este token não tem permissão para esta operação. Escopo necessário: api:escrita.']);
});

test('o logout revoga o token, que deixa de funcionar', function () {
    User::factory()->create(['email' => 'ana@exemplo.com']);
    Client::factory()->asPersonalAccessTokenClient()->create();
    $token = $this->postJson(route('api.v1.login'), ['email' => 'ana@exemplo.com', 'password' => 'password'])->json('data.token');

    $this->withToken($token)->postJson(route('api.v1.logout'))->assertNoContent();

    // Esquece o usuário guardado pelo guard na requisição anterior, como numa nova requisição real.
    $this->app['auth']->forgetGuards();
    $this->withToken($token)->getJson(route('api.v1.usuario'))
        ->assertUnauthorized()
        ->assertExactJson(['message' => MENSAGEM_NAO_AUTENTICADO]);
});

test('sem token a API responde 401 em JSON', function (string $metodo, string $endereco) {
    $this->json($metodo, $endereco)
        ->assertUnauthorized()
        ->assertExactJson(['message' => MENSAGEM_NAO_AUTENTICADO]);
})->with([
    'listar atividades' => ['GET', '/api/v1/atividades'],
    'cadastrar atividade' => ['POST', '/api/v1/atividades'],
    'excluir atividade' => ['DELETE', '/api/v1/atividades/1'],
    'logout' => ['POST', '/api/v1/logout'],
]);

test('responde em JSON mesmo quando o cliente não envia o cabeçalho Accept', function () {
    $this->get('/api/v1/atividades')
        ->assertUnauthorized()
        ->assertExactJson(['message' => MENSAGEM_NAO_AUTENTICADO]);
});

test('token inventado responde 401', function () {
    $this->withToken('token-inventado')
        ->getJson(route('api.v1.atividades.index'))
        ->assertUnauthorized();
});

test('o token de um app de IA conectado ao MCP não acessa a API', function () {
    Passport::actingAs(User::factory()->create(), ['mcp:use']);

    $this->getJson(route('api.v1.atividades.index'))
        ->assertForbidden()
        ->assertExactJson(['message' => 'Este token não tem permissão para esta operação. Escopo necessário: api:leitura.']);
});

test('apps conectados pelo OAuth do MCP não recebem os escopos da API, mesmo pedindo', function () {
    $cliente = Client::factory()->asPublic()->create();
    $pedido = [new Scope('mcp:use'), new Scope('api:leitura'), new Scope('api:escrita')];

    $escopos = app(ScopeRepository::class)->finalizeScopes($pedido, 'authorization_code', new EntidadeDoCliente($cliente->getKey()));

    expect(array_map(fn (Scope $escopo): string => $escopo->getIdentifier(), $escopos))->toBe(['mcp:use']);
});

test('endereço que não existe na API responde 404 em JSON', function () {
    $this->getJson('/api/v1/endereco-inexistente')
        ->assertNotFound()
        ->assertExactJson(['message' => 'Endereço não encontrado na API.']);
});
