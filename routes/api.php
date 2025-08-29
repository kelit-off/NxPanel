<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DataBaseController;
use App\Http\Controllers\ServerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post("/auth/login", [AuthController::class, "login"]);

// Extérieur
Route::post("/auth/register", [AuthController::class, "register"]);
Route::post("/website/create", [ServerController::class, "create_website"]);

// Interne
Route::post('/add-server', [ServerController::class, 'store']);
Route::post('/create-database', [DataBaseController::class, "create"]);