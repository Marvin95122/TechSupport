@extends('layouts.app')
@section('title', 'Panel principal')
@section('content')
    @php
        $esAdministrador = auth()->user()->role === 'administrador';
        $esTecnico = auth()->user()->role === 'tecnico';
        $tituloIndicadores = $esAdministrador
            ? 'Indicadores generales'
            : ($esTecnico ? 'Mi carga de trabajo' : 'Resumen de mis solicitudes');
    @endphp

    <div class="mb-4">
        <p class="text-uppercase fw-semibold text-secondary small mb-1">Tu espacio de trabajo</p>
        <h1 class="ts-page-heading">Bienvenido, {{ auth()->user()->name }}</h1>
        <p class="text-secondary mb-0">Gestiona tus actividades de soporte desde un mismo lugar.</p>
    </div>

    <section class="mb-4" aria-labelledby="tsIndicadoresTitulo">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
            <div>
                <h2 class="h5 fw-bold mb-1" id="tsIndicadoresTitulo">{{ $tituloIndicadores }}</h2>
                <p class="text-secondary small mb-0">{{ $alcanceIndicadores }}. Selecciona un indicador para consultar sus tickets.</p>
            </div>
            <span class="badge text-bg-light border text-secondary">Datos en tiempo real</span>
        </div>
        <div class="row g-3">
            @foreach ($metricas as $metrica)
                <div class="col-6 col-md-4 col-xl">
                    <a class="ts-stat-card h-100 text-decoration-none"
                       href="{{ route('tickets.index', ['estado' => $metrica['id']]) }}"
                       data-status="{{ $metrica['nombre'] }}"
                       data-count="{{ $metrica['total'] }}"
                       data-indicator="{{ $metrica['nombre'] }}:{{ $metrica['total'] }}"
                       aria-label="Consultar {{ $metrica['total'] }} tickets en estado {{ $metrica['nombre'] }}">
                        <span class="ts-stat-label">{{ $metrica['nombre'] }}</span>
                        <strong class="ts-stat-value">{{ number_format($metrica['total']) }}</strong>
                        <small>Ver solicitudes <span aria-hidden="true">→</span></small>
                    </a>
                </div>
            @endforeach
        </div>
    </section>

    <section class="ts-panel">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-4">
            <div>
                <h2 class="h5 fw-bold mb-1">Accesos rápidos</h2>
                <p class="small text-secondary mb-0">Abre directamente los módulos disponibles para tu perfil.</p>
            </div>
            <span class="badge text-bg-light border text-secondary">Panel por rol</span>
        </div>
        <div class="row g-3">
            @if ($esAdministrador)
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
                        <span class="ts-access-icon ts-access-icon--muted" aria-hidden="true"><svg class="ts-icon"><use href="#ts-icon-monitor"/></svg></span>
                        <h3 class="h6 fw-bold">Equipos tecnológicos</h3>
                        <p class="text-secondary small">Registra, consulta, edita y desactiva equipos.</p>
                        <a href="{{ route('admin.equipos.index') }}" class="btn btn-primary btn-sm mt-auto align-self-start">Abrir equipos <svg class="ts-icon ts-icon-inline"><use href="#ts-icon-arrow"/></svg></a>
                    </div>
                </div>
                <div class="col-md-6 col-xl-4">
                    <div class="ts-access-card h-100">
                        <span class="ts-access-icon ts-access-icon--muted" aria-hidden="true"><svg class="ts-icon"><use href="#ts-icon-chart"/></svg></span>
                        <h3 class="h6 fw-bold">Consultas de tickets</h3>
                        <p class="text-secondary small">Busca solicitudes por folio, usuario, técnico o equipo y combina filtros.</p>
                        <a href="{{ route('tickets.index') }}" class="btn btn-primary btn-sm mt-auto align-self-start">Consultar tickets <svg class="ts-icon ts-icon-inline"><use href="#ts-icon-arrow"/></svg></a>
                    </div>
                </div>
                <div class="col-md-6 col-xl-4">
                    <div class="ts-access-card h-100">
                        <span class="ts-access-icon" aria-hidden="true"><svg class="ts-icon"><use href="#ts-icon-history"/></svg></span>
                        <h3 class="h6 fw-bold">Historial de atención</h3>
                        <p class="text-secondary small">Accede a las cronologías de tickets y revisa sus intervenciones.</p>
                        <a href="{{ route('historial.index') }}" class="btn btn-primary btn-sm mt-auto align-self-start">Abrir historial <svg class="ts-icon ts-icon-inline"><use href="#ts-icon-arrow"/></svg></a>
                    </div>
                </div>
            @elseif ($esTecnico)
                <div class="col-md-6 col-xl-4">
                    <div class="ts-access-card h-100">
                        <span class="ts-access-icon ts-access-icon--muted" aria-hidden="true"><svg class="ts-icon"><use href="#ts-icon-clipboard"/></svg></span>
                        <h3 class="h6 fw-bold">Mis asignaciones</h3>
                        <p class="text-secondary small">Consulta los tickets que tienes asignados y accede a su seguimiento.</p>
                        <a href="{{ route('tecnico.asignaciones.index') }}" class="btn btn-primary btn-sm mt-auto align-self-start">Abrir mis asignaciones <svg class="ts-icon ts-icon-inline"><use href="#ts-icon-arrow"/></svg></a>
                    </div>
                </div>
                <div class="col-md-6 col-xl-4">
                    <div class="ts-access-card h-100">
                        <span class="ts-access-icon" aria-hidden="true"><svg class="ts-icon"><use href="#ts-icon-chart"/></svg></span>
                        <h3 class="h6 fw-bold">Consultas</h3>
                        <p class="text-secondary small">Busca y filtra únicamente los tickets asignados a tu cuenta.</p>
                        <a href="{{ route('tickets.index') }}" class="btn btn-primary btn-sm mt-auto align-self-start">Consultar tickets <svg class="ts-icon ts-icon-inline"><use href="#ts-icon-arrow"/></svg></a>
                    </div>
                </div>
                <div class="col-md-6 col-xl-4">
                    <div class="ts-access-card h-100">
                        <span class="ts-access-icon" aria-hidden="true"><svg class="ts-icon"><use href="#ts-icon-history"/></svg></span>
                        <h3 class="h6 fw-bold">Historial de atención</h3>
                        <p class="text-secondary small">Consulta las cronologías de tus tickets asignados.</p>
                        <a href="{{ route('historial.index') }}" class="btn btn-primary btn-sm mt-auto align-self-start">Abrir historial <svg class="ts-icon ts-icon-inline"><use href="#ts-icon-arrow"/></svg></a>
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
    </section>
@endsection
