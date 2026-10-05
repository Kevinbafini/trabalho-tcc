<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Os campos de custo guardam os totais calculados pelo CustoAtividadeService
     * no momento do lançamento (snapshot), assim como a safra do talhão.
     */
    public function up(): void
    {
        Schema::create('atividades_agricolas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('propriedade_id')->constrained('propriedades')->cascadeOnDelete();
            $table->foreignId('talhao_id')->constrained('talhoes')->restrictOnDelete();
            $table->date('data');
            $table->string('tipo', 30);
            $table->string('safra', 9);
            $table->text('descricao')->nullable();
            $table->decimal('custo_insumos', 12, 2)->default(0);
            $table->decimal('custo_maquinas', 12, 2)->default(0);
            $table->decimal('custo_mao_obra', 12, 2)->default(0);
            $table->decimal('custo_total', 12, 2)->default(0);
            $table->timestamps();

            $table->index(['propriedade_id', 'data']);
            $table->index(['talhao_id', 'data']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('atividades_agricolas');
    }
};
