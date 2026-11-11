<?php

use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// Placeholders: cada tela é uma história de usuário (issue no GitHub, ver docs/HISTORIAS_USUARIO.md)
Route::view('/mesario', 'placeholders.mesario')->name('mesario');
Route::view('/urna/{terminal}', 'placeholders.urna')->name('urna');
Route::view('/admin', 'placeholders.admin')->name('admin');
