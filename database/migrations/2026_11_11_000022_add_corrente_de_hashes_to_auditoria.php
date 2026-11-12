<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * H11 fase 4 · Corrente de hashes da auditoria (D27, docs/AUDITORIA.md seção 7).
 *
 * - `auditoria.sequencia`: a posição do evento na corrente (1, 2, 3...). Eventos gravados antes
 *   desta migration ficam sem sequência nem hash: a corrente começa no primeiro evento depois dela.
 * - `auditoria_cabeca`: uma linha só, com a última sequência e o último hash. Quem grava um
 *   evento trava esta linha (`FOR UPDATE`), para duas gravações ao mesmo tempo não lerem o mesmo
 *   "anterior". Apagar o último evento por fora também é detectado: a cabeça não bate.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auditoria', function (Blueprint $table) {
            $table->unsignedBigInteger('sequencia')->nullable()->unique()->after('dados');
        });

        Schema::create('auditoria_cabeca', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->unsignedBigInteger('ultima_sequencia')->default(0);
            $table->char('ultimo_hash', 64)->nullable();
        });

        DB::table('auditoria_cabeca')->insert(['id' => 1, 'ultima_sequencia' => 0, 'ultimo_hash' => null]);
    }

    public function down(): void
    {
        Schema::dropIfExists('auditoria_cabeca');
        Schema::table('auditoria', function (Blueprint $table) {
            $table->dropUnique(['sequencia']);
            $table->dropColumn('sequencia');
        });
    }
};
