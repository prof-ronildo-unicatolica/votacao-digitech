<?php

use App\Http\Controllers\Api\StatusController;
use Illuminate\Support\Facades\Route;

Route::get('/health', [StatusController::class, 'health'])->name('api.health');
Route::get('/terminais/{terminal}/status', [StatusController::class, 'terminal'])->name('api.terminal.status');
