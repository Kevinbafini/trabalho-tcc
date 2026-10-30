<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\DadosDemonstracaoService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

/**
 * Prepara a propriedade de demonstração para um usuário (existente ou novo).
 *
 * Exemplos:
 *   php artisan demo:gerar aluno01@fatec.sp.gov.br --nome="Aluno 01" --senha=sus2026
 *   php artisan demo:gerar admin@admin.com
 */
#[Signature('demo:gerar {email : E-mail do usuário} {--nome= : Nome, caso o usuário precise ser criado} {--senha= : Senha, caso o usuário precise ser criado}')]
#[Description('Cria a propriedade de demonstração (talhões, recursos e atividades) para um usuário')]
class GerarDadosDemonstracao extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(DadosDemonstracaoService $demonstracao): int
    {
        $email = Str::lower($this->texto($this->argument('email')) ?? '');
        $usuario = User::query()->where('email', $email)->first();

        if ($usuario === null) {
            $senha = $this->texto($this->option('senha')) ?? Str::password(10, symbols: false);

            $usuario = User::query()->create([
                'name' => $this->texto($this->option('nome')) ?? 'Usuário de demonstração',
                'email' => $email,
                'password' => $senha,
            ]);

            $this->components->info("Usuário criado: {$email} (senha: {$senha})");
        }

        if ($usuario->propriedades()->where('nome', DadosDemonstracaoService::NOME_DA_PROPRIEDADE)->exists()) {
            $this->components->warn('Este usuário já possui a propriedade de demonstração. Nada foi alterado.');

            return self::SUCCESS;
        }

        $propriedade = $demonstracao->gerar($usuario);

        $this->components->info(sprintf(
            '"%s" criada para %s: %d talhões, %d atividades, custo total de %s.',
            $propriedade->nome,
            $usuario->email,
            $propriedade->talhoes()->count(),
            $propriedade->atividades()->count(),
            Number::currency(floatval($propriedade->atividades()->sum('custo_total'))),
        ));

        return self::SUCCESS;
    }

    /**
     * Valor de um argumento ou opção do comando, ou null quando não foi informado.
     */
    private function texto(mixed $valor): ?string
    {
        return is_string($valor) && $valor !== '' ? $valor : null;
    }
}
