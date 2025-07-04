<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post("/auth/register", [AuthController::class, "register"]);
Route::post("/auth/users/", [AuthController::class, "getUser"]);
