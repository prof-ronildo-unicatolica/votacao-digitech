<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** `numero` é a chave natural do terminal (aparece na URL da urna: /urna/{terminal}). */
return new class extends Migration
{
    /** Aplica a migration. */
    public function up(): void
    {
        Schema::create('terminais', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('numero')->unique();
            $table->timestamps();
        });
    }

    /** Desfaz a migration. */
    public function down(): void
    {
        Schema::dropIfExists('terminais');
    }
};
