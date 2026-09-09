<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Eleitor NÃO é usuário do sistema (não faz login). `matricula` é a chave natural.
 */
return new class extends Migration
{
    /** Aplica a migration. */
    public function up(): void
    {
        Schema::create('eleitores', function (Blueprint $table) {
            $table->id();
            $table->string('matricula', 20)->unique();
            $table->timestamps();
        });
    }

    /** Desfaz a migration. */
    public function down(): void
    {
        Schema::dropIfExists('eleitores');
    }
};
