<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * H4: nome, turma e e-mail do eleitor (D17).
 *
 * O eleitorado é um cadastro único de alunos, apto em toda eleição: não há tabela
 * `eleicao_eleitor`. `nome` tem default '' só para os eleitores que já existiam antes
 * desta migration; o formulário e o CSV exigem o nome.
 */
return new class extends Migration
{
    /** Aplica a migration. */
    public function up(): void
    {
        Schema::table('eleitores', function (Blueprint $table) {
            $table->string('nome', 150)->default('')->after('matricula')->index();
            $table->string('turma', 50)->nullable()->after('nome');
            $table->string('email', 255)->nullable()->after('turma');
        });
    }

    /** Desfaz a migration. */
    public function down(): void
    {
        Schema::table('eleitores', function (Blueprint $table) {
            $table->dropIndex(['nome']);
            $table->dropColumn(['nome', 'turma', 'email']);
        });
    }
};
