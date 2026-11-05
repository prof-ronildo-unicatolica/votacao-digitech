<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Composição da chapa: qual aluno (pela matrícula, via eleitores) ocupa qual cargo.
 *
 * - UNIQUE (chapa_id, cargo_id): um candidato por cargo em cada chapa.
 * - UNIQUE (eleicao_id, eleitor_id): o aluno concorre em UMA chapa e UM cargo por eleição.
 *   Pode concorrer de novo em outra eleição.
 * - FKs compostas (eleicao_id, chapa_id) e (eleicao_id, cargo_id): chapa e cargo são da MESMA
 *   eleição do candidato. Sem isso, a chapa da eleição A poderia ocupar um cargo da eleição B.
 * - eleitor_id RESTRICT: aluno que é candidato não pode ser apagado (a chapa ficaria incompleta).
 * - CASCADE a partir de eleição, chapa e cargo: apagar uma chapa inscrita errada apaga a
 *   composição dela. Chapa com votos já é protegida pelo RESTRICT de votos (D5). Quando a
 *   composição pode mudar (só durante o período de inscrição) é regra da aplicação (H14).
 *
 * Candidatar-se NÃO altera eleitores nem sessoes_votacao: o candidato vota normalmente.
 */
return new class extends Migration
{
    /** Aplica a migration. */
    public function up(): void
    {
        Schema::create('candidatos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('eleicao_id')->constrained('eleicoes')->cascadeOnDelete();
            $table->uuid('chapa_id');
            $table->uuid('cargo_id');
            $table->foreignUuid('eleitor_id')->constrained('eleitores')->restrictOnDelete();
            $table->timestamps();

            $table->foreign(['eleicao_id', 'chapa_id'])
                ->references(['eleicao_id', 'id'])->on('chapas')
                ->cascadeOnDelete();
            $table->foreign(['eleicao_id', 'cargo_id'])
                ->references(['eleicao_id', 'id'])->on('cargos')
                ->cascadeOnDelete();

            $table->unique(['chapa_id', 'cargo_id']);
            $table->unique(['eleicao_id', 'eleitor_id']);
        });
    }

    /** Desfaz a migration. */
    public function down(): void
    {
        Schema::dropIfExists('candidatos');
    }
};
