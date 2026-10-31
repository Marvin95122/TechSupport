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
            <section class="ts-module-card" aria-label="Seguimiento del ticket">
                <h2 class="h5 fw-bold mb-3">Estado de la solicitud</h2>
                <div class="d-flex gap-3 align-items-start mb-3">
                    <span class="badge text-bg-primary">1</span>
                    <div><strong class="d-block">Solicitud registrada</strong><p class="text-secondary small mb-0">El folio identifica tu reporte.</p></div>
                </div>
                <div class="d-flex gap-3 align-items-start">
                    <span class="badge text-bg-secondary">2</span>
                    <div><strong class="d-block">Atención técnica</strong><p class="text-secondary small mb-0">La asignación, actualización de estados y seguimiento se incorporarán en el Sprint 5.</p></div>
                </div>
            </section>
        </div>
    </div>
@endsection
