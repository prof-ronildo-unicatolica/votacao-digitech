<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Chapa pertence a uma eleição. `numero` é a chave natural (o que o eleitor digita/vê):
 * único dentro da eleição, pode repetir entre eleições.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chapas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eleicao_id')->constrained('eleicoes')->cascadeOnDelete();
            $table->unsignedSmallInteger('numero');
            $table->timestamps();

            $table->unique(['eleicao_id', 'numero']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chapas');
    }
};
