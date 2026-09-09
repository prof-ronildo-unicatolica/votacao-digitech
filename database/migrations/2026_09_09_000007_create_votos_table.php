<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SIGILO DO VOTO: esta tabela NÃO tem (e não pode ganhar) eleitor_id, terminal_id,
 * sessao_id ou mesario_id. "Quem votou" fica em sessoes_votacao; "em quem votou" fica aqui.
 * chapa_id nulo fica reservado para voto em branco/nulo (a equipe decide como representar).
 * O teste tests/Feature/SigiloDoVotoTest.php falha se alguém quebrar essa regra.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('votos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eleicao_id')->constrained('eleicoes')->cascadeOnDelete();
            $table->foreignId('chapa_id')->nullable()->constrained('chapas')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('votos');
    }
};
