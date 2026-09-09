<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Só chaves e constraints. Os demais atributos (título, período, situação...)
 * são definidos pela equipe em novas migrations: `php artisan make:migration`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eleicoes', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eleicoes');
    }
};
