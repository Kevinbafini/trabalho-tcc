<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Implementos não têm custo próprio: o custo operacional está
     * incorporado ao custo/hora da máquina que os traciona.
     */
    public function up(): void
    {
        Schema::create('atividade_implemento', function (Blueprint $table) {
            $table->foreignId('atividade_agricola_id')->constrained('atividades_agricolas')->cascadeOnDelete();
            $table->foreignId('implemento_id')->constrained('implementos')->restrictOnDelete();

            $table->primary(['atividade_agricola_id', 'implemento_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('atividade_implemento');
    }
};
