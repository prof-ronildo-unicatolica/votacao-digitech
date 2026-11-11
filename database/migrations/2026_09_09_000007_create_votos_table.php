<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SIGILO DO VOTO: esta tabela NÃO tem (e não pode ganhar) eleitor_id, terminal_id,
 * sessao_id ou mesario_id. "Quem votou" fica em sessoes_votacao; "em quem votou" fica aqui.
 * chapa_id nulo fica reservado para voto em branco/nulo (a equipe decide como representar).
 * O teste tests/Feature/ConstraintsTest.php falha se alguém quebrar essa regra.
 *
 * Também NÃO tem timestamps nem chave sequencial/ordenada: com created_at ou id crescente,
 * bastaria cruzar a ORDEM/HORÁRIO dos votos com sessoes_votacao para descobrir o voto de
 * cada eleitor. Por isso o id é UUID v4 aleatório (ver App\Models\Voto::newUniqueId).
 *
 * INTEGRIDADE DO RESULTADO:
 * - FK composta (eleicao_id, chapa_id) → chapas (eleicao_id, id): o voto só pode ir para uma
 *   chapa da MESMA eleição. Com chapa_id nulo (branco/nulo) a FK não é verificada.
 * - RESTRICT nas duas FKs: chapa ou eleição que já recebeu votos não pode ser apagada. Com
 *   SET NULL, apagar uma chapa transformaria os votos dela em branco/nulo, alterando o resultado;
 *   com CASCADE, a apuração sumiria.
 */
return new class extends Migration
{
    /** Aplica a migration. */
    public function up(): void
    {
        Schema::create('votos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('eleicao_id')->constrained('eleicoes')->restrictOnDelete();
            $table->uuid('chapa_id')->nullable();

            $table->foreign(['eleicao_id', 'chapa_id'])
                ->references(['eleicao_id', 'id'])->on('chapas')
                ->restrictOnDelete();
        });
    }

    /** Desfaz a migration. */
    public function down(): void
    {
        Schema::dropIfExists('votos');
    }
};
