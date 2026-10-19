<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [
        AuthenticatedSessionController::class,
        'create',
    ])->name('login');

    Route::post('/login', [
        AuthenticatedSessionController::class,
        'store',
    ])
        ->middleware('throttle:5,1')
        ->name('login.store');
});

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::view('/dashboard', 'dashboard')
        ->name('dashboard');

    Route::post('/logout', [
        AuthenticatedSessionController::class,
        'destroy',
    ])->name('logout');

    Route::prefix('admin')
        ->name('admin.')
        ->middleware('role:administrador')
        ->group(function (): void {
            Route::get('/usuarios', [
                UserController::class,
                'index',
            ])->name('users.index');

            Route::get('/usuarios/crear', [
                UserController::class,
                'create',
            ])->name('users.create');

            Route::post('/usuarios', [
                UserController::class,
                'store',
            ])->name('users.store');

            Route::get('/usuarios/{user}/editar', [
                UserController::class,
                'edit',
            ])->name('users.edit');

            Route::put('/usuarios/{user}', [
                UserController::class,
                'update',
            ])->name('users.update');

            Route::patch('/usuarios/{user}/estado', [
                UserController::class,
                'toggleStatus',
            ])->name('users.toggle-status');
        });
});