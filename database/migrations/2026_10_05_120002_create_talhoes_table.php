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
        Schema::create('talhoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('propriedade_id')->constrained('propriedades')->cascadeOnDelete();
            $table->string('nome', 100);
            $table->decimal('area', 10, 2);
            $table->string('cultura', 60);
            $table->string('safra', 9);
            $table->string('status', 20)->default('em_cultivo');
            $table->timestamps();

            $table->unique(['propriedade_id', 'nome']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talhoes');
    }
};
