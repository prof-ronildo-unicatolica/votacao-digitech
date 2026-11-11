<?php

use App\Http\Controllers\Dev\RecarregarController;
use App\Http\Controllers\EstiloController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\UrnaController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// Telas com a identidade visual; a lógica é das histórias H1 (login) e H7/H8 (urna).
Route::view('/login', 'auth.login')->name('login');
Route::get('/urna/{terminal}', UrnaController::class)->name('urna');

// Placeholders: cada tela é uma história de usuário (issue no GitHub, ver docs/HISTORIAS_USUARIO.md)
Route::view('/mesario', 'placeholders.mesario')->name('mesario');
Route::view('/admin', 'placeholders.admin')->name('admin');

// Vitrine da identidade visual (docs/IDENTIDADE_VISUAL.md), com dados fictícios. Fora de produção.
if (! app()->isProduction()) {
    Route::get('/estilo', [EstiloController::class, 'index'])->name('estilo');
    Route::get('/estilo/telas/{tela}', [EstiloController::class, 'tela'])
        ->whereIn('tela', array_keys(EstiloController::TELAS))
        ->name('estilo.tela');
}

// Auto-refresh do navegador ao salvar arquivos (só em desenvolvimento local, sem Node).
if (app()->isLocal()) {
    Route::get('/_dev/recarregar', RecarregarController::class)->name('dev.recarregar');
}
