<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\DadosDemonstracaoService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Cria o usuário administrador com a propriedade de demonstração.
     * Para preparar dados de outros usuários: php artisan demo:gerar {email}
     */
    public function run(DadosDemonstracaoService $demonstracao): void
    {
        $administrador = User::factory()->create([
            'name' => 'admin user',
            'email' => 'admin@admin.com',
        ]);

        $demonstracao->gerar($administrador);
    }
}
