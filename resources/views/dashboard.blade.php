@extends('layouts.app')
@section('title', 'Panel principal')
@section('content')
    <div class="mb-4">
        <p class="text-uppercase fw-semibold text-secondary small mb-1">Tu espacio de trabajo</p>
        <h1 class="ts-page-heading">Bienvenido, {{ auth()->user()->name }}</h1>
        <p class="text-secondary mb-0">Gestiona tus actividades de soporte desde un mismo lugar.</p>
    </div>

    {{-- Indicadores sin datos simulados. Se activarán cuando exista el módulo de tickets. --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="ts-stat-card">
                <span class="ts-stat-label">Pendientes</span>
                <strong class="ts-stat-value">—</strong>
                <small>Se habilitará en Sprint 7</small>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="ts-stat-card">
                <span class="ts-stat-label">Asignados</span>
                <strong class="ts-stat-value">—</strong>
                <small>Se habilitará en Sprint 5</small>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="ts-stat-card">
                <span class="ts-stat-label">En proceso</span>
                <strong class="ts-stat-value">—</strong>
                <small>Se habilitará en Sprint 7</small>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="ts-stat-card">
                <span class="ts-stat-label">Resueltos</span>
                <strong class="ts-stat-value">—</strong>
                <small>Se habilitará en Sprint 7</small>
            </div>
        </div>
    </div>
    <section class="ts-panel">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-4">
            <div>
                <h2 class="h5 fw-bold mb-1">Accesos rápidos</h2>
                <p class="small text-secondary mb-0">Tus módulos disponibles y las siguientes funcionalidades del proyecto.</p>
            </div>
            <span class="badge text-bg-light border text-secondary">Panel por rol</span>
        </div>
        <div class="row g-3">
            @if (auth()->user()->role === 'administrador')
                <div class="col-md-6 col-xl-4">
                    <div class="ts-access-card h-100">
                        <span class="ts-access-icon" aria-hidden="true"><svg class="ts-icon"><use href="#ts-icon-users"/></svg></span>
                        <h3 class="h6 fw-bold">Administración de usuarios</h3>
                        <p class="text-secondary small">Gestiona cuentas, roles y estados de acceso.</p>
                        <a href="{{ route('admin.users.index') }}" class="btn btn-primary btn-sm mt-auto align-self-start">Abrir usuarios <svg class="ts-icon ts-icon-inline"><use href="#ts-icon-arrow"/></svg></a>
                    </div>
                </div>
                <div class="col-md-6 col-xl-4">
                    <div class="ts-access-card h-100">
                        <span class="ts-access-icon" aria-hidden="true"><svg class="ts-icon"><use href="#ts-icon-monitor"/></svg></span>
                        <h3 class="h6 fw-bold">Equipos tecnológicos</h3>
                        <p class="text-secondary small">Registra, consulta, edita y desactiva equipos.</p>
                        <a href="{{ route('admin.equipos.index') }}" class="btn btn-primary btn-sm mt-auto align-self-start">Abrir equipos <svg class="ts-icon ts-icon-inline"><use href="#ts-icon-arrow"/></svg></a>
                    </div>
                </div>
                <div class="col-md-6 col-xl-4">
                    <div class="ts-access-card h-100">
                        <span class="ts-access-icon ts-access-icon--muted" aria-hidden="true"><svg class="ts-icon"><use href="#ts-icon-ticket"/></svg></span>
                        <h3 class="h6 fw-bold">Solicitudes de soporte</h3>
                        <p class="text-secondary small">Consulta solicitudes registradas y revisa su detalle.</p>
                        <a href="{{ route('tickets.index') }}" class="btn btn-primary btn-sm mt-auto align-self-start">Ver tickets <svg class="ts-icon ts-icon-inline"><use href="#ts-icon-arrow"/></svg></a>
                    </div>
                </div>
            @elseif (auth()->user()->role === 'tecnico')
                <div class="col-md-6 col-xl-4">
                    <div class="ts-access-card h-100">
                        <span class="ts-access-icon ts-access-icon--muted" aria-hidden="true"><svg class="ts-icon"><use href="#ts-icon-clipboard"/></svg></span>
                        <h3 class="h6 fw-bold">Mis asignaciones</h3>
                        <p class="text-secondary small">Consulta y seguimiento de los tickets asignados.</p>
                        <span class="badge text-bg-secondary mt-auto align-self-start">Próximo · Sprint 5</span>
                    </div>
                </div>
            @elseif (auth()->user()->role === 'solicitante')
                <div class="col-md-6 col-xl-4">
                    <div class="ts-access-card h-100">
                        <span class="ts-access-icon ts-access-icon--muted" aria-hidden="true"><svg class="ts-icon"><use href="#ts-icon-ticket"/></svg></span>
                        <h3 class="h6 fw-bold">Nueva solicitud</h3>
                        <p class="text-secondary small">Registra incidencias y obtén un folio de seguimiento.</p>
                        <a href="{{ route('tickets.create') }}" class="btn btn-primary btn-sm mt-auto align-self-start">Registrar solicitud <svg class="ts-icon ts-icon-inline"><use href="#ts-icon-arrow"/></svg></a>
                    </div>
                </div>
                <div class="col-md-6 col-xl-4">
                    <div class="ts-access-card h-100">
                        <span class="ts-access-icon" aria-hidden="true"><svg class="ts-icon"><use href="#ts-icon-clipboard"/></svg></span>
                        <h3 class="h6 fw-bold">Mis tickets</h3>
                        <p class="text-secondary small">Consulta tus solicitudes, folios y estados.</p>
                        <a href="{{ route('tickets.index') }}" class="btn btn-primary btn-sm mt-auto align-self-start">Abrir mis tickets <svg class="ts-icon ts-icon-inline"><use href="#ts-icon-arrow"/></svg></a>
                    </div>
                </div>
            @endif
        </div>
        <p class="small text-secondary mt-4 mb-0">Las funciones pendientes se incorporarán progresivamente. No se muestran métricas simuladas.</p>
    </section>
@endsection
