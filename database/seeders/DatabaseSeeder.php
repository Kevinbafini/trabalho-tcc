<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\DadosDemonstracaoService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Cria o usuário administrador (config/demonstracao.php) com os dados de
     * demonstração. Pode rodar mais de uma vez: o que já existe não é duplicado.
     *
     * Outros usuários: php artisan demo:gerar {email}, ou o próprio usuário em
     * Configurações → Dados de demonstração.
     */
    public function run(DadosDemonstracaoService $demonstracao): void
    {
        /** @var array{nome: string, email: string, senha: string|null} $dados */
        $dados = config('demonstracao.administrador');

        // Em produção, nunca a senha conhecida "password".
        $senha = filled($dados['senha']) ? $dados['senha'] : (app()->isProduction() ? Str::password(16) : 'password');

        $administrador = User::query()->firstOrCreate(
            ['email' => $dados['email']],
            ['name' => $dados['nome'], 'password' => $senha],
        );

        if ($administrador->wasRecentlyCreated) {
            $this->command?->info(filled($dados['senha'])
                ? "Administrador criado: {$administrador->email} (senha definida em ADMIN_SENHA)."
                : "Administrador criado: {$administrador->email} (senha: {$senha}). Guarde esta senha.");
        }

        if ($demonstracao->ativar($administrador)) {
            $this->command?->info("Dados de demonstração criados para {$administrador->email}.");
        }
    }
}
