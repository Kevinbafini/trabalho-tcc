<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('insumos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('propriedade_id')->constrained('propriedades')->cascadeOnDelete();
            $table->string('nome', 150);
            $table->string('categoria', 30);
            $table->string('unidade', 10);
            $table->decimal('valor_unitario', 10, 2);
            $table->decimal('quantidade_estoque', 12, 3)->default(0);
            $table->string('status', 20)->default('ativo');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('insumos');
    }
};
