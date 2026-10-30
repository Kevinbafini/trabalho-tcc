<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Marca as propriedades criadas pelos dados de demonstração, para que
     * possam ser removidas sem tocar nos cadastros reais do usuário.
     */
    public function up(): void
    {
        Schema::table('propriedades', function (Blueprint $table) {
            $table->boolean('demonstracao')->default(false)->after('observacoes');
            $table->index(['user_id', 'demonstracao']);
        });

        // A fazenda de demonstração já criada antes desta coluna existir.
        DB::table('propriedades')
            ->where('nome', 'Fazenda Santa Helena (demonstração)')
            ->update(['demonstracao' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('propriedades', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'demonstracao']);
            $table->dropColumn('demonstracao');
        });
    }
};
