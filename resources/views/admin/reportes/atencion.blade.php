@extends('layouts.app')

@section('title', 'Reporte de atención')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <p class="text-uppercase fw-semibold text-secondary small mb-1">REPORTES / ATENCIÓN</p>
            <h1 class="ts-page-heading">Reporte general de atención</h1>
            <p class="text-secondary mb-0">Consulta el comportamiento de las solicitudes y el avance de su atención.</p>
        </div>
        <div class="d-flex flex-wrap gap-2 ts-report-actions">
            <a href="{{ route('admin.reportes.atencion.imprimir', $filtros) }}"
               class="btn btn-outline-secondary" target="_blank" rel="noopener">
                <svg class="ts-icon ts-icon-inline"><use href="#ts-icon-activity"/></svg>
                Vista de impresión
            </a>
            <a href="{{ route('admin.reportes.atencion.exportar', $filtros) }}" class="btn btn-primary">
                <svg class="ts-icon ts-icon-inline"><use href="#ts-icon-chart"/></svg>
                Exportar CSV
            </a>
            <a href="{{ route('admin.reportes.atencion.exportar.excel', $filtros) }}"
            class="btn btn-success">
                <i class="bi bi-file-earmark-excel"></i>
                Exportar Excel (.xlsx)
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger mb-4" role="alert">
            <strong>No fue posible aplicar los filtros.</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="ts-panel mb-4 ts-report-filters" aria-labelledby="filtrosReporteTitulo">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
            <div>
                <h2 class="h5 fw-bold mb-1" id="filtrosReporteTitulo">Filtros del reporte</h2>
                <p class="text-secondary small mb-0">El periodo se determina por la fecha de registro de cada solicitud.</p>
            </div>
            <span class="badge text-bg-light border text-secondary">{{ $periodoTexto }}</span>
        </div>

        <form method="GET" action="{{ route('admin.reportes.atencion') }}">
            <div class="row g-3">
                <div class="col-12 col-sm-6 col-lg-3">
                    <label for="fecha_inicio" class="form-label">Fecha inicial</label>
                    <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control"
                           value="{{ $filtros['fecha_inicio'] ?? '' }}">
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <label for="fecha_fin" class="form-label">Fecha final</label>
                    <input type="date" name="fecha_fin" id="fecha_fin" class="form-control"
                           value="{{ $filtros['fecha_fin'] ?? '' }}">
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <label for="estado_id" class="form-label">Estado</label>
                    <select name="estado_id" id="estado_id" class="form-select">
                        <option value="">Todos los estados</option>
                        @foreach ($estados as $estado)
                            <option value="{{ $estado->id }}" @selected((string) ($filtros['estado_id'] ?? '') === (string) $estado->id)>
                                {{ $estado->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <label for="categoria_id" class="form-label">Categoría</label>
                    <select name="categoria_id" id="categoria_id" class="form-select">
                        <option value="">Todas las categorías</option>
                        @foreach ($categorias as $categoria)
                            <option value="{{ $categoria->id }}" @selected((string) ($filtros['categoria_id'] ?? '') === (string) $categoria->id)>
                                {{ $categoria->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <label for="prioridad_id" class="form-label">Prioridad</label>
                    <select name="prioridad_id" id="prioridad_id" class="form-select">
                        <option value="">Todas las prioridades</option>
                        @foreach ($prioridades as $prioridad)
                            <option value="{{ $prioridad->id }}" @selected((string) ($filtros['prioridad_id'] ?? '') === (string) $prioridad->id)>
                                {{ $prioridad->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <label for="tecnico_id" class="form-label">Técnico responsable</label>
                    <select name="tecnico_id" id="tecnico_id" class="form-select">
                        <option value="">Todos los técnicos</option>
                        @foreach ($tecnicos as $tecnico)
                            <option value="{{ $tecnico->id }}" @selected((string) ($filtros['tecnico_id'] ?? '') === (string) $tecnico->id)>
                                {{ $tecnico->name }}{{ $tecnico->is_active ? '' : ' (inactivo)' }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text">Se incluyen técnicos inactivos para consultar registros históricos.</div>
                </div>
                <div class="col-12 d-flex flex-wrap align-items-center gap-2 pt-1">
                    <button type="submit" class="btn btn-primary">Aplicar filtros</button>
                    <a href="{{ route('admin.reportes.atencion') }}" class="btn btn-outline-secondary">Limpiar filtros</a>
                </div>
            </div>
        </form>
    </section>

    <section class="mb-4" aria-labelledby="resumenReporteTitulo">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
            <div>
                <h2 class="h5 fw-bold mb-1" id="resumenReporteTitulo">Resumen de atención</h2>
                <p class="text-secondary small mb-0">Indicadores calculados sobre las solicitudes que coinciden con los filtros.</p>
            </div>
            <span class="badge text-bg-light border text-secondary">{{ number_format($resumen['total']) }} registros</span>
        </div>

        <div class="row g-3">
            <div class="col-6 col-xl">
                <div class="ts-report-metric h-100" data-report-metric="total" data-value="{{ $resumen['total'] }}">
                    <span class="ts-report-metric-label">Total de solicitudes</span>
                    <strong>{{ number_format($resumen['total']) }}</strong>
                    <small>Registros encontrados</small>
                </div>
            </div>
            <div class="col-6 col-xl">
                <div class="ts-report-metric h-100" data-report-metric="pendientes" data-value="{{ $resumen['pendientes'] }}">
                    <span class="ts-report-metric-label">Pendientes</span>
                    <strong>{{ number_format($resumen['pendientes']) }}</strong>
                    <small>Sin asignación técnica</small>
                </div>
            </div>
            <div class="col-6 col-xl">
                <div class="ts-report-metric h-100" data-report-metric="en-atencion" data-value="{{ $resumen['en_atencion'] }}">
                    <span class="ts-report-metric-label">En atención</span>
                    <strong>{{ number_format($resumen['en_atencion']) }}</strong>
                    <small>Asignadas o en proceso</small>
                </div>
            </div>
            <div class="col-6 col-xl">
                <div class="ts-report-metric h-100" data-report-metric="finalizadas" data-value="{{ $resumen['finalizados'] }}">
                    <span class="ts-report-metric-label">Finalizadas</span>
                    <strong>{{ number_format($resumen['finalizados']) }}</strong>
                    <small>Resueltas o cerradas</small>
                </div>
            </div>
            <div class="col-12 col-xl">
                <div class="ts-report-metric h-100" data-report-metric="promedio-resolucion">
                    <span class="ts-report-metric-label">Tiempo promedio de resolución</span>
                    <strong class="ts-report-metric-duration">{{ $resumen['promedio_resolucion'] }}</strong>
                    <small>Desde el registro hasta la resolución</small>
                </div>
            </div>
        </div>
    </section>

    <section class="ts-panel mb-4" aria-labelledby="estadoReporteTitulo">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
            <div>
                <h2 class="h5 fw-bold mb-1" id="estadoReporteTitulo">Solicitudes por estado</h2>
                <p class="text-secondary small mb-0">Distribución de los registros incluidos en el reporte.</p>
            </div>
        </div>
        <div class="row g-3">
            @foreach ($resumen['estados'] as $estado)
                <div class="col-6 col-md">
                    <div class="ts-report-status">
                        <span>{{ $estado['nombre'] }}</span>
                        <strong>{{ number_format($estado['total']) }}</strong>
                        <div class="progress" role="progressbar"
                             aria-label="{{ $estado['nombre'] }}"
                             aria-valuenow="{{ $resumen['total'] > 0 ? round(($estado['total'] / $resumen['total']) * 100) : 0 }}"
                             aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar" style="width: {{ $resumen['total'] > 0 ? ($estado['total'] / $resumen['total']) * 100 : 0 }}%"></div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="ts-panel" aria-labelledby="detalleReporteTitulo">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
            <div>
                <h2 class="h5 fw-bold mb-1" id="detalleReporteTitulo">Detalle de solicitudes</h2>
                <p class="text-secondary small mb-0">{{ $periodoTexto }} · Ordenadas de la más reciente a la más antigua.</p>
            </div>
            <span class="badge text-bg-light border text-secondary">{{ number_format($tickets->total()) }} resultados</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th scope="col">Folio</th>
                        <th scope="col">Asunto</th>
                        <th scope="col">Solicitante</th>
                        <th scope="col">Técnico</th>
                        <th scope="col">Categoría</th>
                        <th scope="col">Prioridad</th>
                        <th scope="col">Estado</th>
                        <th scope="col">Registro</th>
                        <th scope="col">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tickets as $ticket)
                        <tr>
                            <td class="text-nowrap fw-semibold">{{ $ticket->folio }}</td>
                            <td class="ts-report-subject">{{ $ticket->asunto }}</td>
                            <td>
                                <span class="d-block">{{ $ticket->solicitante?->name ?? 'No disponible' }}</span>
                                <small class="text-secondary">{{ $ticket->solicitante?->email }}</small>
                            </td>
                            <td>{{ $ticket->tecnico?->name ?? 'Sin asignar' }}</td>
                            <td>{{ $ticket->categoria?->nombre ?? '—' }}</td>
                            <td>{{ $ticket->prioridad?->nombre ?? '—' }}</td>
                            <td><span class="badge text-bg-light border">{{ $ticket->estado?->nombre ?? 'No disponible' }}</span></td>
                            <td class="text-nowrap">{{ $ticket->creado_en?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td>
                                <a href="{{ route('tickets.show', $ticket) }}" class="btn btn-sm btn-outline-primary">Ver</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5">
                                <strong class="d-block mb-1">No se encontraron solicitudes.</strong>
                                <span class="text-secondary small">Modifica los filtros para consultar otros registros.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($tickets->hasPages())
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-3">
                <small class="text-secondary">
                    Mostrando {{ $tickets->firstItem() }}–{{ $tickets->lastItem() }} de {{ $tickets->total() }} solicitudes.
                </small>
                {{ $tickets->links() }}
            </div>
        @endif
    </section>
@endsection
