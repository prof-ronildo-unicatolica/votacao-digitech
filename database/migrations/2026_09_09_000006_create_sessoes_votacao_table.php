<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Uma linha por eleitor por eleição: é a "assinatura na ata".
 * O UNIQUE (eleicao_id, eleitor_id) impede, no banco, que o mesmo eleitor
 * seja liberado/vote duas vezes na mesma eleição.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sessoes_votacao', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eleicao_id')->constrained('eleicoes')->cascadeOnDelete();
            $table->foreignId('eleitor_id')->constrained('eleitores')->cascadeOnDelete();
            $table->foreignId('terminal_id')->constrained('terminais');
            $table->foreignId('liberada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['aberta', 'votou', 'expirada', 'cancelada'])->default('aberta');
            $table->dateTime('liberada_em');
            $table->dateTime('expira_em');
            $table->dateTime('votou_em')->nullable();
            $table->timestamps();

            $table->unique(['eleicao_id', 'eleitor_id']);
            $table->index(['terminal_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessoes_votacao');
    }
};
