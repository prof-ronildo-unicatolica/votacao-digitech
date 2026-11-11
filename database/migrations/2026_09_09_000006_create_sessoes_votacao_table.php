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
 *
 * A ata não pode perder quem votou nem quem liberou:
 * - eleitor_id RESTRICT: eleitor que já foi liberado não pode ser apagado (senão o
 *   comparecimento fica menor que o número de votos e some o registro de que ele votou).
 * - mesario_id obrigatório e RESTRICT: toda liberação tem um mesário responsável; usuário
 *   com liberações não pode ser apagado (desative-o, ver H1).
 */
return new class extends Migration
{
    /** Aplica a migration. */
    public function up(): void
    {
        Schema::create('sessoes_votacao', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('eleicao_id')->constrained('eleicoes')->cascadeOnDelete();
            $table->foreignUuid('eleitor_id')->constrained('eleitores')->restrictOnDelete();
            $table->foreignUuid('terminal_id')->constrained('terminais')->restrictOnDelete();
            $table->foreignId('mesario_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['eleicao_id', 'eleitor_id']);
        });
    }

    /** Desfaz a migration. */
    public function down(): void
    {
        Schema::dropIfExists('sessoes_votacao');
    }
};
