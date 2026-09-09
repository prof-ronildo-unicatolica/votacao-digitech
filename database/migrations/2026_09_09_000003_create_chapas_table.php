<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chapas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eleicao_id')->constrained('eleicoes')->cascadeOnDelete();
            $table->unsignedSmallInteger('numero');
            $table->string('nome', 120);
            $table->string('descricao', 500)->nullable();
            $table->timestamps();

            $table->unique(['eleicao_id', 'numero']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chapas');
    }
};
