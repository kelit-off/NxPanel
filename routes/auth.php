<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get("/login", [AuthController::class, "viewLogin"]);
Route::post("/login", [AuthController::class, "login"])->name("login");