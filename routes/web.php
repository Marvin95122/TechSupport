<?php

use App\Http\Controllers\Admin\TicketAsignacionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Equipos\EquipoHistorialController;
use App\Http\Controllers\Tickets\MisAsignacionesController;
use App\Http\Controllers\Tickets\TicketEstadoController;
use App\Http\Controllers\Tickets\TicketObservacionController;
use App\Http\Controllers\Tickets\TicketSolucionController;
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

// Sprint 3 / Checkpoint C1: registro y consulta de equipos.
Route::middleware(['auth', 'active', 'role:administrador'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/equipos', [\App\Http\Controllers\Admin\EquipoController::class, 'index'])->name('equipos.index');
        Route::get('/equipos/crear', [\App\Http\Controllers\Admin\EquipoController::class, 'create'])->name('equipos.create');
        Route::post('/equipos', [\App\Http\Controllers\Admin\EquipoController::class, 'store'])->name('equipos.store');
        Route::get('/equipos/{equipo}', [\App\Http\Controllers\Admin\EquipoController::class, 'show'])->name('equipos.show');
        Route::post('/areas', [\App\Http\Controllers\Admin\EquipoController::class, 'storeArea'])->name('areas.store');
    });

// Sprint 3 / Checkpoint C2: edición y baja lógica de equipos.
Route::middleware(['auth', 'active', 'role:administrador'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/equipos/{equipo}/editar', [\App\Http\Controllers\Admin\EquipoController::class, 'edit'])->name('equipos.edit');
        Route::put('/equipos/{equipo}', [\App\Http\Controllers\Admin\EquipoController::class, 'update'])->name('equipos.update');
        Route::patch('/equipos/{equipo}/desactivar', [\App\Http\Controllers\Admin\EquipoController::class, 'deactivate'])->name('equipos.deactivate');
    });

// Sprint 4 / Checkpoint C1: alta de solicitudes. HU-09.
Route::middleware(['auth', 'active', 'role:solicitante'])
    ->group(function (): void {
        Route::get('/tickets/crear', [\App\Http\Controllers\Tickets\TicketController::class, 'create'])
            ->name('tickets.create');
        Route::post('/tickets', [\App\Http\Controllers\Tickets\TicketController::class, 'store'])
            ->middleware('throttle:12,1')
            ->name('tickets.store');
    });

// Sprint 4 / HU-10: listado para administrador y solicitante.
Route::middleware(['auth', 'active', 'role:administrador,solicitante'])
    ->group(function (): void {
        Route::get('/tickets', [\App\Http\Controllers\Tickets\TicketConsultaController::class, 'index'])
            ->name('tickets.index');
    });

// Sprint 4 / HU-12 y Sprint 5 / HU-14: detalle con acceso limitado por propietario o asignación.
Route::middleware(['auth', 'active', 'role:administrador,solicitante,tecnico'])
    ->group(function (): void {
        Route::get('/tickets/{ticket}', [\App\Http\Controllers\Tickets\TicketConsultaController::class, 'show'])
            ->name('tickets.show');
    });

// Sprint 5 / HU-13: asignación de tickets a técnicos activos.
Route::middleware(['auth', 'active', 'role:administrador'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::post('/tickets/{ticket}/asignar', [
            TicketAsignacionController::class,
            'store',
        ])
            ->middleware('throttle:10,1')
            ->name('tickets.assign');
    });

// Sprint 5 / HU-14: actualización secuencial del estado por el técnico asignado.
Route::middleware(['auth', 'active', 'role:tecnico'])
    ->group(function (): void {
        Route::post('/tickets/{ticket}/estado', [
            TicketEstadoController::class,
            'update',
        ])
            ->middleware('throttle:12,1')
            ->name('tickets.estado.update');
    });

// Sprint 5 / HU-15: listado privado de asignaciones del técnico autenticado.
Route::middleware(['auth', 'active', 'role:tecnico'])
    ->get('/mis-asignaciones', [
        MisAsignacionesController::class,
        'index',
    ])
    ->name('tecnico.asignaciones.index');

// Sprint 6 / HU-17: registro append-only de observaciones y actividades.
Route::middleware(['auth', 'active', 'role:tecnico'])
    ->post('/tickets/{ticket}/observaciones', [
        TicketObservacionController::class,
        'store',
    ])
    ->middleware('throttle:20,1')
    ->name('tickets.observaciones.store');

// Sprint 6 / HU-18: registro de diagnóstico y solución aplicada.
Route::middleware(['auth', 'active', 'role:tecnico'])
    ->post('/tickets/{ticket}/soluciones', [
        TicketSolucionController::class,
        'store',
    ])
    ->middleware('throttle:10,1')
    ->name('tickets.soluciones.store');

// Sprint 6 / HU-20: historial de incidencias de un equipo.
Route::middleware(['auth', 'active', 'role:administrador,tecnico'])
    ->get('/equipos/{equipo}/historial', [
        EquipoHistorialController::class,
        'show',
    ])
    ->name('equipos.historial');
