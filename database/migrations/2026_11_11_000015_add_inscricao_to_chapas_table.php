<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * H14: dados da inscrição da chapa (D14, D18).
 *
 * - `nome`: único na eleição. Chapas que já existiam recebem "Chapa N" antes do UNIQUE.
 * - `situacao`: pendente → homologada | indeferida (App\Enums\SituacaoChapa). Toda inscrição
 *   entra pendente; só homologada vai para a urna e para a apuração (H3).
 * - `origem`: formulário público (H14) ou CSV da comissão (H15).
 * - `contato_email` e `contato_telefone`: como a comissão fala com o responsável pela chapa.
 *   Não aparecem em nenhuma página pública.
 */
return new class extends Migration
{
    /** Aplica a migration. */
    public function up(): void
    {
        Schema::table('chapas', function (Blueprint $table) {
            $table->string('nome', 100)->default('')->after('numero');
            $table->string('situacao', 20)->default('pendente')->after('nome')->index();
            $table->string('origem', 20)->default('formulario')->after('situacao');
            $table->string('contato_email', 255)->nullable()->after('origem');
            $table->string('contato_telefone', 20)->nullable()->after('contato_email');
        });

        DB::table('chapas')->where('nome', '')->orderBy('id')->each(function ($chapa) {
            DB::table('chapas')->where('id', $chapa->id)->update(['nome' => "Chapa {$chapa->numero}"]);
        });

        Schema::table('chapas', function (Blueprint $table) {
            $table->unique(['eleicao_id', 'nome']);
        });
    }

    /** Desfaz a migration. */
    public function down(): void
    {
        Schema::table('chapas', function (Blueprint $table) {
            $table->dropUnique(['eleicao_id', 'nome']);
            $table->dropIndex(['situacao']);
            $table->dropColumn(['nome', 'situacao', 'origem', 'contato_email', 'contato_telefone']);
        });
    }
};
