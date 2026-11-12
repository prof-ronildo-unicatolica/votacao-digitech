<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * H15 (D19): matrícula de quem trabalha na eleição e também é aluno.
 *
 * Opcional: só para admin ou mesário que está no eleitorado. Com ela, a inscrição de chapa
 * (formulário H14 e CSV H15) recusa esse aluno como candidato: quem libera votos ou conduz
 * a eleição não concorre. Única: uma matrícula é de uma pessoa só.
 */
return new class extends Migration
{
    /** Aplica a migration. */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('matricula', 20)->nullable()->unique()->after('email');
        });
    }

    /** Desfaz a migration. */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['matricula']);
            $table->dropColumn('matricula');
        });
    }
};
