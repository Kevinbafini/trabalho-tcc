<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * custo_hora é o valor da hora do funcionário no dia do lançamento (snapshot).
     */
    public function up(): void
    {
        Schema::create('atividade_funcionario', function (Blueprint $table) {
            $table->foreignId('atividade_agricola_id')->constrained('atividades_agricolas')->cascadeOnDelete();
            $table->foreignId('funcionario_id')->constrained('funcionarios')->restrictOnDelete();
            $table->decimal('horas', 8, 2);
            $table->decimal('custo_hora', 10, 2);
            $table->decimal('custo_total', 12, 2);

            $table->primary(['atividade_agricola_id', 'funcionario_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('atividade_funcionario');
    }
};
