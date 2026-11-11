<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * H13: título do cargo (Presidente, Vice-presidente, 1º Secretário...), único na eleição.
 *
 * Os cargos que já existiam (criados só com a ordem) recebem "Cargo N" antes do UNIQUE,
 * senão o índice falharia com vários títulos vazios na mesma eleição. O admin renomeia na tela.
 */
return new class extends Migration
{
    /** Aplica a migration. */
    public function up(): void
    {
        Schema::table('cargos', function (Blueprint $table) {
            $table->string('titulo', 80)->default('')->after('eleicao_id');
        });

        DB::table('cargos')->where('titulo', '')->orderBy('id')->each(function ($cargo) {
            DB::table('cargos')->where('id', $cargo->id)->update(['titulo' => "Cargo {$cargo->ordem}"]);
        });

        Schema::table('cargos', function (Blueprint $table) {
            $table->unique(['eleicao_id', 'titulo']);
        });
    }

    /** Desfaz a migration. */
    public function down(): void
    {
        Schema::table('cargos', function (Blueprint $table) {
            $table->dropUnique(['eleicao_id', 'titulo']);
            $table->dropColumn('titulo');
        });
    }
};
