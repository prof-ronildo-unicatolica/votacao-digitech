<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Assinatura na ata": a liberação de um eleitor, por um mesário, em um terminal, em uma eleição.
 *
 * UNIQUE (eleicao_id, eleitor_id) é a constraint mais importante do sistema:
 * garante no banco que um eleitor só tem UMA sessão por eleição (sem voto duplo).
 * Situação da sessão, horários de liberação/expiração/voto: a equipe define.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sessoes_votacao', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eleicao_id')->constrained('eleicoes')->cascadeOnDelete();
            $table->foreignId('eleitor_id')->constrained('eleitores')->cascadeOnDelete();
            $table->foreignId('terminal_id')->constrained('terminais')->restrictOnDelete();
            $table->foreignId('mesario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['eleicao_id', 'eleitor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessoes_votacao');
    }
};
