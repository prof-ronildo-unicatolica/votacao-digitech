<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * H6: ciclo de vida da sessão (D21). Uma linha por eleitor e eleição (UNIQUE), só com o estado atual.
 *
 * - `situacao`: aberta → votou | cancelada. "Expirada" não é gravada: é uma sessão aberta com
 *   `expira_em` no passado (App\Models\SessaoVotacao::situacaoAtual). Assim nenhuma tarefa
 *   agendada precisa varrer as sessões.
 * - `liberada_em` e `expira_em`: a liberação vale 30 minutos. Re-liberar atualiza a mesma linha.
 * - `votou_em`: quando o eleitor votou. Fica na SESSÃO (que é a ata), nunca no voto (D3).
 * - `cancelada_em`: o mesário cancelou (ex.: eleitor foi para o terminal errado).
 * - Índice (terminal_id, situacao): achar a sessão aberta de cada terminal.
 */
return new class extends Migration
{
    /** Aplica a migration. */
    public function up(): void
    {
        Schema::table('sessoes_votacao', function (Blueprint $table) {
            $table->string('situacao', 20)->default('aberta')->after('mesario_id');
            $table->timestamp('liberada_em')->nullable()->after('situacao');
            $table->timestamp('expira_em')->nullable()->after('liberada_em');
            $table->timestamp('votou_em')->nullable()->after('expira_em');
            $table->timestamp('cancelada_em')->nullable()->after('votou_em');
            $table->index(['terminal_id', 'situacao']);
        });
    }

    /** Desfaz a migration. */
    public function down(): void
    {
        Schema::table('sessoes_votacao', function (Blueprint $table) {
            $table->dropIndex(['terminal_id', 'situacao']);
            $table->dropColumn(['situacao', 'liberada_em', 'expira_em', 'votou_em', 'cancelada_em']);
        });
    }
};
