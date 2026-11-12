<?php

use App\Http\Controllers\Api\StatusController;
use Illuminate\Support\Facades\Route;

Route::get('/health', [StatusController::class, 'health'])->name('api.health');
