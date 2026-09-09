<?php

use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// Placeholders: cada tela vira uma história de usuário no Trello (ver docs/HISTORIAS_USUARIO.md)
Route::view('/mesario', 'placeholders.mesario')->name('mesario');
Route::view('/urna/{terminal}', 'placeholders.urna')->name('urna');
Route::view('/admin', 'placeholders.admin')->name('admin');
