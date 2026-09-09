<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SIGILO DO VOTO: esta tabela NÃO tem eleitor_id, terminal_id nem sessao_id.
 * "Quem votou" fica em sessoes_votacao; "em quem votou" fica aqui, sem ligação.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('votos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eleicao_id')->constrained('eleicoes')->cascadeOnDelete();
            $table->foreignId('chapa_id')->nullable()->constrained('chapas')->nullOnDelete();
            $table->enum('tipo', ['valido', 'branco', 'nulo'])->default('valido');
            $table->dateTime('registrado_em');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('votos');
    }
};
