<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eleicoes', function (Blueprint $table) {
            $table->id();
            $table->string('titulo', 200);
            $table->dateTime('inicio');
            $table->dateTime('fim');
            $table->boolean('ativa')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eleicoes');
    }
};
