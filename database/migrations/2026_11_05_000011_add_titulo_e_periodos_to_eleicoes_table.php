<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * H2: título, período de inscrição de chapas, período de votação e eleição ativa.
 *
 * Ordem das datas (validada na aplicação, App\Http\Requests\EleicaoRequest):
 *   inscricao_abre_em < inscricao_fecha_em <= votacao_abre_em < votacao_fecha_em
 * `datetime` (e não `timestamp`) guarda a hora local de America/Fortaleza sem conversão.
 * As datas são nullable só para eleições que já existiam antes desta migration; o formulário exige todas.
 *
 * SÓ UMA ELEIÇÃO ATIVA, garantido pelo banco: `ativa` é TRUE ou NULL (nunca FALSE) e tem
 * UNIQUE. O MySQL aceita vários NULL num índice único, mas só um TRUE. O model Eleicao
 * converte false em NULL ao gravar (D15).
 */
return new class extends Migration
{
    /** Aplica a migration. */
    public function up(): void
    {
        Schema::table('eleicoes', function (Blueprint $table) {
            $table->string('titulo', 150)->default('')->after('id');
            $table->dateTime('inscricao_abre_em')->nullable()->after('titulo');
            $table->dateTime('inscricao_fecha_em')->nullable()->after('inscricao_abre_em');
            $table->dateTime('votacao_abre_em')->nullable()->after('inscricao_fecha_em');
            $table->dateTime('votacao_fecha_em')->nullable()->after('votacao_abre_em');
            $table->boolean('ativa')->nullable()->unique()->after('votacao_fecha_em');
        });
    }

    /** Desfaz a migration. */
    public function down(): void
    {
        Schema::table('eleicoes', function (Blueprint $table) {
            $table->dropUnique(['ativa']);
            $table->dropColumn(['titulo', 'inscricao_abre_em', 'inscricao_fecha_em', 'votacao_abre_em', 'votacao_fecha_em', 'ativa']);
        });
    }
};
