@extends('layouts.app')
@section('title', 'Detalle de ticket')
@section('content')
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
        <div>
            <p class="text-uppercase text-secondary small fw-semibold mb-1">Soporte técnico / Solicitudes</p>
            <h1 class="ts-page-heading">Detalle de solicitud</h1>
            <p class="text-secondary mb-0">Folio: <strong>{{ $ticket->folio }}</strong></p>
        </div>
        <a href="{{ route('tickets.index') }}" class="btn btn-outline-secondary">Volver a {{ $esAdministrador ? 'tickets' : 'mis tickets' }}</a>
    </div>

    <div class="row g-4">
        <div class="col-12 col-xl-8">
            <section class="ts-module-card" aria-label="Datos de la solicitud">
                <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
                    <div>
                        <span class="text-secondary small d-block mb-1">Asunto</span>
                        <h2 class="h4 fw-bold mb-0">{{ $ticket->asunto }}</h2>
                    </div>
                    <span class="badge text-bg-{{ $ticket->estado?->nombre === 'Pendiente' ? 'warning' : 'secondary' }}">
                        {{ $ticket->estado?->nombre ?? 'No disponible' }}
                    </span>
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
                        <p class="text-secondary small mb-0">
                            Estado actual: {{ $ticket->estado?->nombre ?? 'No disponible' }}.
                        </p>
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
                        <p class="text-secondary small">
                            Selecciona un técnico activo para hacerse responsable de esta solicitud.
                        </p>

                        @if ($tecnicos->isNotEmpty())
                            <form action="{{ route('admin.tickets.assign', $ticket) }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label for="tecnico_id" class="form-label fw-semibold">Técnico responsable</label>
                                    <select id="tecnico_id" name="tecnico_id"
                                            class="form-select @error('tecnico_id') is-invalid @enderror" required>
                                        <option value="">Selecciona un técnico</option>
                                        @foreach ($tecnicos as $tecnico)
                                            <option value="{{ $tecnico->id }}" @selected((string) old('tecnico_id') === (string) $tecnico->id)>
                                                {{ $tecnico->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('tecnico_id')
                                        <div class="invalid-feedback d-block" role="alert">{{ $message }}</div>
                                    @enderror
                                </div>
                                <button type="submit" class="btn btn-primary w-100">
                                    Asignar técnico
                                </button>
                            </form>
                        @else
                            <div class="alert alert-warning mb-0" role="status">
                                No hay técnicos activos disponibles para asignar esta solicitud.
                            </div>
                        @endif
                    @else
                        <div class="alert alert-info mb-0" role="status">
                            Este ticket ya no está pendiente y no puede asignarse desde esta pantalla.
                        </div>
                    @endif
                </section>
            @endif
        </div>
    </div>
@endsection
