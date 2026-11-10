@extends('layouts.app')

@section('title', 'Expediente de atención')

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
    @endphp

    <div class="ts-report-detail-header mb-4">
        <div>
            <span class="ts-report-section-kicker">Reportes / Expediente individual</span>
            <h1 class="ts-page-heading mb-1">Expediente de atención</h1>
            <p class="text-secondary mb-0">Consulta los datos de la solicitud y todos los eventos registrados durante su atención.</p>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="badge text-bg-{{ $estadoBadge }}">{{ $ticket->estado?->nombre ?? 'Sin estado' }}</span>
            <a href="{{ route('admin.reportes.atencion', $filtros) }}" class="btn btn-outline-secondary">Volver al reporte</a>
        </div>
    </div>

    <section class="ts-report-ticket-banner mb-4">
        <div>
            <span class="ts-report-ticket-label">Folio de seguimiento</span>
            <strong>{{ $ticket->folio }}</strong>
            <p class="mb-0">{{ $ticket->asunto }}</p>
        </div>
        <div class="ts-report-ticket-banner-meta">
            <span><small>Fecha de registro</small><strong>{{ $ticket->creado_en?->format('d/m/Y H:i') ?? 'No disponible' }}</strong></span>
            <span><small>Prioridad</small><strong>{{ $ticket->prioridad?->nombre ?? 'No disponible' }}</strong></span>
        </div>
    </section>

    <div class="row g-3 mb-4">
        <div class="col-12 col-xl-7">
            <section class="ts-panel h-100">
                <span class="ts-report-section-kicker">Información de la incidencia</span>
                <h2 class="h5 fw-bold mb-3">Datos de la solicitud</h2>
                <h3 class="h6 fw-bold">Descripción</h3>
                <p class="ts-report-description">{{ $ticket->descripcion }}</p>
                <hr>
                <dl class="row g-3 mb-0 ts-report-detail-list">
                    <dt class="col-sm-4">Solicitante</dt><dd class="col-sm-8">{{ $ticket->solicitante?->name ?? 'No disponible' }} <small class="d-block text-secondary">{{ $ticket->solicitante?->email }}</small></dd>
                    <dt class="col-sm-4">Técnico responsable</dt><dd class="col-sm-8">{{ $ticket->tecnico?->name ?? 'Sin asignar' }}</dd>
                    <dt class="col-sm-4">Categoría</dt><dd class="col-sm-8">{{ $ticket->categoria?->nombre ?? 'No disponible' }}</dd>
                    <dt class="col-sm-4">Prioridad</dt><dd class="col-sm-8">{{ $ticket->prioridad?->nombre ?? 'No disponible' }}</dd>
                    <dt class="col-sm-4">Equipo relacionado</dt>
                    <dd class="col-sm-8">
                        @if ($ticket->equipo)
                            {{ $ticket->equipo->codigo }} — {{ $ticket->equipo->tipo }} · {{ $ticket->equipo->marca }} {{ $ticket->equipo->modelo }}
                        @else
                            No se relacionó un equipo.
                        @endif
                    </dd>
                </dl>
            </section>
        </div>
        <div class="col-12 col-xl-5">
            <section class="ts-panel h-100">
                <span class="ts-report-section-kicker">Tiempos del proceso</span>
                <h2 class="h5 fw-bold mb-3">Fechas relevantes</h2>
                <div class="ts-report-date-list">
                    <div><span>Registro</span><strong>{{ $ticket->creado_en?->format('d/m/Y H:i') ?? '—' }}</strong></div>
                    <div><span>Asignación</span><strong>{{ $ticket->asignado_en?->format('d/m/Y H:i') ?? 'Pendiente' }}</strong></div>
                    <div><span>Resolución</span><strong>{{ $ticket->resuelto_en?->format('d/m/Y H:i') ?? 'Sin resolver' }}</strong></div>
                    <div><span>Cierre</span><strong>{{ $ticket->cerrado_en?->format('d/m/Y H:i') ?? 'Sin cerrar' }}</strong></div>
                    <div><span>Asignado por</span><strong>{{ $ticket->asignadoPor?->name ?? 'No disponible' }}</strong></div>
                </div>
                <p class="small text-secondary mt-3 mb-0">Esta vista es de consulta y no modifica la solicitud ni sus registros históricos.</p>
            </section>
        </div>
    </div>

    <section class="ts-panel ts-report-timeline-panel" aria-labelledby="historialReporteTitulo">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-4">
            <div>
                <span class="ts-report-section-kicker">Trazabilidad completa</span>
                <h2 class="h5 fw-bold mb-1" id="historialReporteTitulo">Historial de atención</h2>
                <p class="text-secondary small mb-0">Registro cronológico de cambios de estado, observaciones, actividades y soluciones.</p>
            </div>
            <span class="ts-report-result-count">{{ $historial->count() }} eventos</span>
        </div>

        @if ($historial->isEmpty())
            <div class="ts-report-chart-empty">Todavía no hay eventos registrados para esta solicitud.</div>
        @else
            <div class="ts-report-timeline">
                @foreach ($historial as $evento)
                    <article class="ts-report-timeline-item" data-event-type="{{ $evento['tipo'] }}">
                        <span class="ts-report-timeline-marker" aria-hidden="true"></span>
                        <div class="ts-report-timeline-content">
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <strong>{{ $evento['titulo'] }}</strong>
                                    <span class="ts-report-event-type ts-report-event-{{ $evento['tipo'] }}">{{ ucfirst($evento['tipo']) }}</span>
                                </div>
                                <time class="text-secondary small">{{ $evento['fecha']?->format('d/m/Y H:i') ?? 'Fecha no disponible' }}</time>
                            </div>
                            <p class="mb-2 mt-2">{{ $evento['descripcion'] }}</p>
                            <small class="text-secondary">Responsable: {{ $evento['autor'] }}</small>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
@endsection
