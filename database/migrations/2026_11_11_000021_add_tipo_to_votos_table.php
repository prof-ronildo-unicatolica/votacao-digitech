<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * H8: branco e nulo são coisas diferentes na apuração (D24). `tipo` diz o que é o voto:
 * `chapa` (com chapa_id), `branco` ou `nulo` (os dois sem chapa_id).
 *
 * No MySQL/MariaDB um CHECK garante a combinação no banco. O SQLite (testes) não aceita
 * ADD CONSTRAINT depois de criada a tabela; lá a regra fica na aplicação (App\Support\RegistroDeVoto).
 * Continua sem horário e sem nada do eleitor (D3).
 */
return new class extends Migration
{
    /** Aplica a migration. */
    public function up(): void
    {
        Schema::table('votos', function (Blueprint $table) {
            $table->string('tipo', 10)->default('chapa')->after('chapa_id');
        });

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE votos ADD CONSTRAINT votos_tipo_chapa CHECK ((tipo = 'chapa' AND chapa_id IS NOT NULL) OR (tipo IN ('branco', 'nulo') AND chapa_id IS NULL))");
        }
    }

    /** Desfaz a migration. */
    public function down(): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE votos DROP CHECK votos_tipo_chapa');
        }

        Schema::table('votos', function (Blueprint $table) {
            $table->dropColumn('tipo');
        });
    }
};
