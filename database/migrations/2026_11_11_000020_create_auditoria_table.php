<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * H11 · Registro de auditoria (D22, docs/AUDITORIA.md): quem fez o quê, quando e de onde.
 *
 * - Só acrescenta: a aplicação nunca altera nem apaga um evento (App\Models\EventoAuditoria).
 * - Nunca em quem alguém votou: o voto não gera evento, e nenhum evento de sessão guarda a chapa.
 * - `ator` guarda o nome e o perfil no momento, para o registro sobreviver à edição do usuário.
 * - `alvo_id` sem FK: o alvo pode ser excluído e o evento continua.
 * - `hash`: reservado para a corrente de hashes (fase 4).
 */
return new class extends Migration
{
    /** Aplica a migration. */
    public function up(): void
    {
        Schema::create('auditoria', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->timestamp('ocorrido_em', 6);
            $table->string('acao', 50);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ator', 255);
            $table->string('ip', 45)->nullable();
            $table->foreignUuid('eleicao_id')->nullable()->constrained('eleicoes')->nullOnDelete();
            $table->string('alvo_tipo', 50)->nullable();
            $table->string('alvo_id', 36)->nullable();
            $table->json('dados');
            $table->char('hash', 64)->nullable();

            $table->index('ocorrido_em');
            $table->index(['acao', 'ocorrido_em']);
            $table->index(['eleicao_id', 'ocorrido_em']);
            $table->index(['alvo_tipo', 'alvo_id']);
        });
    }

    /** Desfaz a migration. */
    public function down(): void
    {
        Schema::dropIfExists('auditoria');
    }
};
