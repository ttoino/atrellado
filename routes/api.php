<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

// Loaded under the /api prefix with the "api" middleware group.
Route::get('/user', [LoginController::class, 'getUser'])->middleware('auth:api');
