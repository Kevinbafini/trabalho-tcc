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
        Schema::create('mensagens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversa_id')->constrained()->cascadeOnDelete();
            $table->string('papel', 20);
            $table->longText('conteudo');

            // Preenchidos nas respostas da IA: quem respondeu, quais ferramentas usou e quantos tokens gastou.
            $table->string('provedor', 20)->nullable();
            $table->string('modelo', 60)->nullable();
            $table->json('ferramentas')->nullable();
            $table->unsignedInteger('tokens_entrada')->default(0);
            $table->unsignedInteger('tokens_saida')->default(0);
            $table->unsignedInteger('tokens_cache')->default(0);
            $table->timestamps();

            $table->index(['conversa_id', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mensagens');
    }
};
