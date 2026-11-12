<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * H3: análise da chapa pela comissão (D20).
 *
 * - `motivo_indeferimento`: obrigatório para indeferir; apagado quando a chapa volta para pendente.
 * - `analisada_em` e `analisada_por_id`: quem mudou a situação por último e quando. O histórico
 *   completo (homologou, depois reabriu...) fica na auditoria (H11). Apagar o usuário não apaga
 *   a chapa: a coluna vira NULL.
 */
return new class extends Migration
{
    /** Aplica a migration. */
    public function up(): void
    {
        Schema::table('chapas', function (Blueprint $table) {
            $table->string('motivo_indeferimento', 500)->nullable()->after('situacao');
            $table->timestamp('analisada_em')->nullable()->after('motivo_indeferimento');
            $table->foreignId('analisada_por_id')->nullable()->after('analisada_em')->constrained('users')->nullOnDelete();
        });
    }

    /** Desfaz a migration. */
    public function down(): void
    {
        Schema::table('chapas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('analisada_por_id');
            $table->dropColumn(['motivo_indeferimento', 'analisada_em']);
        });
    }
};
