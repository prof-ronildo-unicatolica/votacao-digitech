<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * H1: perfil do usuário (admin ou mesário) e desativação sem apagar.
 *
 * - `perfil` é uma coluna (e não uma tabela de perfis): são só dois, fixos (D15).
 *   Valores em App\Enums\Perfil.
 * - `ativo`: usuário desativado não entra mais, mas continua no banco, porque quem já
 *   liberou sessões não pode ser apagado (sessoes_votacao.mesario_id RESTRICT).
 */
return new class extends Migration
{
    /** Aplica a migration. */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('perfil', 20)->default('mesario')->after('email');
            $table->boolean('ativo')->default(true)->after('perfil');
        });
    }

    /** Desfaz a migration. */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['perfil', 'ativo']);
        });
    }
};
