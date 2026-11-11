<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * H7: quando a cédula apareceu na urna. Com ela, o terminal passa de "liberado" (o eleitor
 * ainda não chegou) para "votando" (a cédula está na tela). Re-liberar volta para NULL.
 */
return new class extends Migration
{
    /** Aplica a migration. */
    public function up(): void
    {
        Schema::table('sessoes_votacao', function (Blueprint $table) {
            $table->timestamp('exibida_em')->nullable()->after('expira_em');
        });
    }

    /** Desfaz a migration. */
    public function down(): void
    {
        Schema::table('sessoes_votacao', function (Blueprint $table) {
            $table->dropColumn('exibida_em');
        });
    }
};
