<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * custo_unitario é o valor do insumo no dia do lançamento: alterar o
     * cadastro do insumo depois não muda o custo das atividades antigas.
     */
    public function up(): void
    {
        Schema::create('atividade_insumo', function (Blueprint $table) {
            $table->foreignId('atividade_agricola_id')->constrained('atividades_agricolas')->cascadeOnDelete();
            $table->foreignId('insumo_id')->constrained('insumos')->restrictOnDelete();
            $table->decimal('quantidade', 12, 3);
            $table->decimal('custo_unitario', 10, 2);
            $table->decimal('custo_total', 12, 2);

            $table->primary(['atividade_agricola_id', 'insumo_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('atividade_insumo');
    }
};
