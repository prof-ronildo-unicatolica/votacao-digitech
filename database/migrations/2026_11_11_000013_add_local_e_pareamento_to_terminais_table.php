<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * H5: local do terminal e pareamento com um computador (D16).
 *
 * A urna não tem mais o ID do terminal na URL: ela é identificada pelo cookie `terminal`,
 * que guarda um token aleatório. No banco fica só o SHA-256 dele (`token_hash`), como uma
 * senha: quem lê o banco não consegue se passar por um terminal. `ativado_em` marca o
 * pareamento, que vale 24 horas. Desativar apaga as duas colunas.
 */
return new class extends Migration
{
    /** Aplica a migration. */
    public function up(): void
    {
        Schema::table('terminais', function (Blueprint $table) {
            $table->string('local', 100)->nullable()->after('numero');
            $table->char('token_hash', 64)->nullable()->unique()->after('local');
            $table->dateTime('ativado_em')->nullable()->after('token_hash');
        });
    }

    /** Desfaz a migration. */
    public function down(): void
    {
        Schema::table('terminais', function (Blueprint $table) {
            $table->dropUnique(['token_hash']);
            $table->dropColumn(['local', 'token_hash', 'ativado_em']);
        });
    }
};
