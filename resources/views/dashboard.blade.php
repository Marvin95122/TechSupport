@extends('layouts.app')

@section('title', 'Panel principal')

@section('content')
    <div class="mb-4">
        <p class="text-uppercase fw-semibold text-secondary small mb-1">Resumen de trabajo</p>
        <h1 class="ts-page-heading">Bienvenido, {{ auth()->user()->name }}</h1>
        <p class="text-secondary mb-0">Accede a las funciones disponibles para tu perfil.</p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="ts-stat-card">
                <span class="ts-stat-label">Pendientes</span>
                <strong class="ts-stat-value">—</strong>
                <small>Disponible en Sprint 7</small>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="ts-stat-card">
                <span class="ts-stat-label">Asignados</span>
                <strong class="ts-stat-value">—</strong>
                <small>Disponible en Sprint 5</small>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="ts-stat-card">
                <span class="ts-stat-label">En proceso</span>
                <strong class="ts-stat-value">—</strong>
                <small>Disponible en Sprint 7</small>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="ts-stat-card">
                <span class="ts-stat-label">Resueltos</span>
                <strong class="ts-stat-value">—</strong>
                <small>Disponible en Sprint 7</small>
            </div>
        </div>
    </div>

    <section class="ts-panel">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h2 class="h5 fw-bold mb-1">Accesos rápidos</h2>
                <p class="small text-secondary mb-0">La navegación se habilitará conforme se incorporen los módulos.</p>
            </div>
        </div>

        <div class="row g-3">
            @if (auth()->user()->role === 'administrador')
                <div class="col-md-6 col-xl-4">
                    <div class="ts-access-card h-100">
                        <h3 class="h6 fw-bold">Administración de usuarios</h3>
                        <p class="text-secondary small">Registrar, editar, activar y desactivar cuentas y gestionar roles.</p>
                        <a href="{{ route('admin.users.index') }}" class="btn btn-primary btn-sm mt-auto align-self-start">Abrir usuarios</a>
                    </div>
                </div>
                <div class="col-md-6 col-xl-4">
                    <div class="ts-access-card h-100">
                        <h3 class="h6 fw-bold">Equipos tecnológicos</h3>
                        <p class="text-secondary small">Registro, consulta, edición y baja lógica de equipos.</p>
                        <span class="badge text-bg-secondary mt-auto align-self-start">En desarrollo · Sprint 3</span>
                    </div>
                </div>
            @elseif (auth()->user()->role === 'tecnico')
                <div class="col-md-6 col-xl-4">
                    <div class="ts-access-card h-100">
                        <h3 class="h6 fw-bold">Mis asignaciones</h3>
                        <p class="text-secondary small">Consulta y seguimiento de tickets asignados.</p>
                        <span class="badge text-bg-secondary mt-auto align-self-start">Disponible en Sprint 5</span>
                    </div>
                </div>
            @elseif (auth()->user()->role === 'solicitante')
                <div class="col-md-6 col-xl-4">
                    <div class="ts-access-card h-100">
                        <h3 class="h6 fw-bold">Nueva solicitud</h3>
                        <p class="text-secondary small">Registro de incidencias y consulta de solicitudes personales.</p>
                        <span class="badge text-bg-secondary mt-auto align-self-start">Disponible en Sprint 4</span>
                    </div>
                </div>
            @endif
        </div>

        <div class="alert alert-light border mt-4 mb-0 small" role="note">
            Los apartados en desarrollo no son enlaces. Sus funciones se habilitarán después de implementar y comprobar los permisos y las operaciones correspondientes.
        </div>
    </section>
@endsection
