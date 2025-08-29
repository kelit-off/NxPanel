<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});

Route::prefix("admin")->group(function () {
    require __DIR__."/admin.php";
});

Route::prefix("auth")->group(function () {
    require __DIR__."/auth.php";
});

Route::get('/', [DashboardController::class, 'dashboard']);
// require __DIR__.'/settings.php';
// require __DIR__.'/auth.php';
