<?php

use App\Http\Controllers\AdminReportController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GenerationController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('history.index'));

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'loginPage'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

    Route::get('/register', [AuthController::class, 'registerPage'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:register');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function (): void {
    Route::get('/history', [GenerationController::class, 'index'])->name('history.index');
    Route::get('/generate', [GenerationController::class, 'create'])->name('generate.create');
    Route::post('/generate/estimate', [GenerationController::class, 'estimate'])->name('generate.estimate');
    Route::post('/generate', [GenerationController::class, 'store'])->name('generate.store');
    Route::get('/admin/report', AdminReportController::class)->name('admin.report');
});
