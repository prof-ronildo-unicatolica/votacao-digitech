<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cargos que TODA chapa da eleição precisa preencher (Presidente, Vice, 1º Secretário...).
 * Cada eleição define os seus: o formulário de inscrição e o CSV usam esta lista.
 *
 * Um cargo = UMA vaga por chapa. "1º e 2º secretário" são dois cargos (ordem 3 e 4), não
 * um cargo com duas vagas: assim UNIQUE (chapa_id, cargo_id) em candidatos basta (D13).
 * `ordem` é a chave natural: a posição do cargo na cédula e no formulário.
 * Título do cargo: atributo da equipe (H13).
 */
return new class extends Migration
{
    /** Aplica a migration. */
    public function up(): void
    {
        Schema::create('cargos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('eleicao_id')->constrained('eleicoes')->cascadeOnDelete();
            $table->unsignedSmallInteger('ordem');
            $table->timestamps();

            $table->unique(['eleicao_id', 'ordem']);
            // Alvo da FK composta de candidatos: o cargo precisa ser DA MESMA eleição da chapa.
            $table->unique(['eleicao_id', 'id']);
        });
    }

    /** Desfaz a migration. */
    public function down(): void
    {
        Schema::dropIfExists('cargos');
    }
};
