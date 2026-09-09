<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eleitores', function (Blueprint $table) {
            $table->id();
            $table->string('matricula', 20)->unique();
            $table->string('nome', 150);
            $table->string('email', 150)->nullable();
            $table->string('turma', 60)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eleitores');
    }
};
