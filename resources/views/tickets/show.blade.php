@extends('layouts.app')
@section('title', 'Detalle de ticket')
@section('content')
    @php
        $estadoBadge = match ($ticket->estado?->nombre) {
            'Pendiente' => 'warning',
            'Asignado' => 'secondary',
            'En proceso' => 'info',
            'Resuelto' => 'success',
            'Cerrado' => 'dark',
            default => 'secondary',
        };
        $rutaVolver = $esTecnico
            ? route('tecnico.asignaciones.index')
            : route('tickets.index');
        $textoVolver = $esAdministrador ? 'consultas' : ($esTecnico ? 'mis asignaciones' : 'mis tickets');
    @endphp

    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
        <div>
            <p class="text-uppercase text-secondary small fw-semibold mb-1">Soporte técnico / Solicitudes</p>
            <h1 class="ts-page-heading">Detalle de solicitud</h1>
            <p class="text-secondary mb-0">Folio: <strong>{{ $ticket->folio }}</strong></p>
        </div>
        <a href="{{ $rutaVolver }}" class="btn btn-outline-secondary">Volver a {{ $textoVolver }}</a>
    </div>

    <div class="row g-4">
        <div class="col-12 col-xl-8">
            <section class="ts-module-card" aria-label="Datos de la solicitud">
                <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
                    <div>
                        <span class="text-secondary small d-block mb-1">Asunto</span>
                        <h2 class="h4 fw-bold mb-0">{{ $ticket->asunto }}</h2>
                    </div>
                    <span class="badge text-bg-{{ $estadoBadge }}">{{ $ticket->estado?->nombre ?? 'No disponible' }}</span>
                </div>
                <h3 class="h6 fw-bold">Descripción de la incidencia</h3>
                <p class="mb-4" style="white-space: pre-wrap; overflow-wrap: anywhere;">{{ $ticket->descripcion }}</p>
                <hr>
                <dl class="row g-3 mb-0">
                    <dt class="col-sm-4">Categoría</dt>
                    <dd class="col-sm-8">{{ $ticket->categoria?->nombre ?? 'No disponible' }}</dd>
                    <dt class="col-sm-4">Prioridad solicitada</dt>
                    <dd class="col-sm-8">{{ $ticket->prioridad?->nombre ?? 'No disponible' }}</dd>
                    <dt class="col-sm-4">Equipo relacionado</dt>
                    <dd class="col-sm-8">
                        @if ($ticket->equipo)
                            {{ $ticket->equipo->codigo }} — {{ $ticket->equipo->tipo }} · {{ $ticket->equipo->marca }} {{ $ticket->equipo->modelo }}
                            @unless ($ticket->equipo->activo)
                                <span class="badge text-bg-secondary ms-1">Equipo inactivo</span>
                            @endunless
                            @if ($esAdministrador || $esTecnico)
                                <div class="mt-2">
                                    <a href="{{ route('equipos.historial', $ticket->equipo) }}" class="btn btn-sm btn-outline-primary">
                                        Consultar historial del equipo
                                    </a>
                                </div>
                            @endif
                        @else
                            Ninguno
                        @endif
                    </dd>
                    <dt class="col-sm-4">Solicitante</dt>
                    <dd class="col-sm-8">{{ $ticket->solicitante?->name ?? 'No disponible' }}</dd>
                    <dt class="col-sm-4">Fecha de registro</dt>
                    <dd class="col-sm-8">{{ $ticket->creado_en?->format('d/m/Y H:i') ?? 'Sin fecha' }}</dd>
                </dl>
            </section>
        </div>

        <div class="col-12 col-xl-4">
            <section class="ts-module-card mb-4" aria-label="Estado del ticket">
                <h2 class="h5 fw-bold mb-3">Estado de la solicitud</h2>
                <div class="d-flex gap-3 align-items-start mb-3">
                    <span class="badge text-bg-primary">1</span>
                    <div>
                        <strong class="d-block">Solicitud registrada</strong>
                        <p class="text-secondary small mb-0">El folio identifica tu reporte.</p>
                    </div>
                </div>
                <div class="d-flex gap-3 align-items-start">
                    <span class="badge text-bg-secondary">2</span>
                    <div>
                        <strong class="d-block">Atención técnica</strong>
                        <p class="text-secondary small mb-0">Estado actual: {{ $ticket->estado?->nombre ?? 'No disponible' }}.</p>
                    </div>
                </div>
            </section>

            @if ($esAdministrador)
                <section class="ts-module-card" aria-label="Asignación técnica">
                    <h2 class="h5 fw-bold mb-2">Asignación técnica</h2>

                    @if ($ticket->tecnico)
                        <div class="alert alert-success mb-3">
                            <strong class="d-block">Técnico responsable</strong>
                            {{ $ticket->tecnico->name }}
                        </div>
                        <dl class="row g-2 small mb-0">
                            <dt class="col-5">Asignado por</dt>
                            <dd class="col-7">{{ $ticket->asignadoPor?->name ?? 'No disponible' }}</dd>
                            <dt class="col-5">Fecha</dt>
                            <dd class="col-7">{{ $ticket->asignado_en?->format('d/m/Y H:i') ?? 'No disponible' }}</dd>
                        </dl>
                    @elseif ($ticket->estado?->nombre === 'Pendiente')
                        <p class="text-secondary small">Busca un técnico activo por nombre o correo. Se mostrarán hasta diez coincidencias.</p>

                        <form action="{{ route('admin.tickets.assign', $ticket) }}" method="POST" data-ts-technician-form>
                            @csrf
                            <div class="mb-3 ts-technician-picker"
                                 data-ts-technician-picker
                                 data-search-url="{{ route('admin.tecnicos.search') }}">
                                <label for="tecnico_busqueda" class="form-label fw-semibold">Buscar técnico responsable</label>
                                <div class="input-group ts-technician-input-group">
                                    <input id="tecnico_busqueda"
                                           type="search"
                                           class="form-control @error('tecnico_id') is-invalid @enderror"
                                           value="{{ $tecnicoSeleccionado?->name ?? '' }}"
                                           placeholder="Escribe al menos 2 caracteres"
                                           autocomplete="off"
                                           required
                                           role="combobox"
                                           aria-autocomplete="list"
                                           aria-expanded="false"
                                           aria-controls="tecnicos-resultados"
                                           aria-describedby="tecnico-ayuda tecnico-estado"
                                           data-ts-technician-input>
                                    <button type="button" class="btn btn-outline-secondary"
                                            data-ts-technician-clear aria-label="Limpiar técnico seleccionado">
                                        Limpiar
                                    </button>
                                </div>
                                <input type="hidden" name="tecnico_id"
                                       value="{{ $tecnicoSeleccionado?->id ?? '' }}"
                                       data-ts-technician-id>
                                <div id="tecnicos-resultados" class="ts-combobox-options" role="listbox"
                                     aria-label="Coincidencias de técnicos" data-ts-technician-options hidden></div>
                                <p id="tecnico-ayuda" class="form-text mb-1">Solo aparecen cuentas activas con rol Técnico.</p>
                                <p id="tecnico-estado" class="form-text mb-0" aria-live="polite" data-ts-technician-status>
                                    {{ $tecnicoSeleccionado ? 'Técnico seleccionado: '.$tecnicoSeleccionado->name : 'Escribe para buscar y selecciona una coincidencia.' }}
                                </p>
                                @error('tecnico_id')
                                    <div class="invalid-feedback d-block" role="alert">{{ $message }}</div>
                                @enderror
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Asignar técnico</button>
                        </form>
                    @else
                        <div class="alert alert-info mb-0" role="status">
                            Este ticket ya no está pendiente y no puede asignarse desde esta pantalla.
                        </div>
                    @endif
                </section>
            @elseif ($esTecnico)
                <section class="ts-module-card" aria-label="Seguimiento técnico">
                    <h2 class="h5 fw-bold mb-2">Seguimiento técnico</h2>
                    <p class="text-secondary small">Actualiza el estado conforme avance la atención de esta solicitud.</p>

                    <dl class="row g-2 small mb-3">
                        <dt class="col-5">Técnico responsable</dt>
                        <dd class="col-7">{{ $ticket->tecnico?->name ?? auth()->user()->name }}</dd>
                        <dt class="col-5">Asignado por</dt>
                        <dd class="col-7">{{ $ticket->asignadoPor?->name ?? 'No disponible' }}</dd>
                        <dt class="col-5">Fecha de asignación</dt>
                        <dd class="col-7">{{ $ticket->asignado_en?->format('d/m/Y H:i') ?? 'No disponible' }}</dd>
                    </dl>

                    @if ($siguienteEstado)
                        <div class="alert alert-info" role="status">Próximo estado: <strong>{{ $siguienteEstado->nombre }}</strong></div>
                        <form action="{{ route('tickets.estado.update', $ticket) }}" method="POST">
                            @csrf
                            <input type="hidden" name="estado_id" value="{{ $siguienteEstado->id }}">
                            @error('estado_id')
                                <div class="alert alert-danger py-2" role="alert">{{ $message }}</div>
                            @enderror
                            <button type="submit" class="btn btn-primary w-100">
                                {{ match ($siguienteEstado->nombre) {
                                    'En proceso' => 'Iniciar atención',
                                    'Resuelto' => 'Marcar como resuelto',
                                    'Cerrado' => 'Cerrar ticket',
                                    default => 'Actualizar estado',
                                } }}
                            </button>
                        </form>
                    @elseif ($ticket->estado?->nombre === 'Cerrado')
                        <div class="alert alert-success mb-0" role="status">Este ticket ya fue cerrado. No admite más cambios de estado.</div>
                    @else
                        <div class="alert alert-warning mb-0" role="status">Este ticket no tiene una transición disponible. Contacta al administrador.</div>
                    @endif
                </section>
            @endif
        </div>
    </div>

    @include('tickets.partials.registro-atencion')
    @include('tickets.partials.historial')
@endsection
