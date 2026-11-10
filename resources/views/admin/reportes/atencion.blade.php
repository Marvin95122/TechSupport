@extends('layouts.app')

@section('title', 'Reportes de atención')

@section('content')
    @php
        $total = (int) $resumen['total'];
        $tasaFinalizacion = $total > 0 ? (int) round(($resumen['finalizados'] / $total) * 100) : 0;
        $coloresEstado = [
            'Pendiente' => '#f59e0b',
            'Asignado' => '#64748b',
            'En proceso' => '#2563eb',
            'Resuelto' => '#10b981',
            'Cerrado' => '#0f766e',
        ];
        $segmentosDonut = [];
        $acumuladoDonut = 0;

        foreach ($resumen['estados'] as $estado) {
            $cantidad = (int) $estado['total'];
            if ($total > 0 && $cantidad > 0) {
                $porcentaje = ($cantidad / $total) * 100;
                $color = $coloresEstado[$estado['nombre']] ?? '#94a3b8';
                $inicio = number_format($acumuladoDonut, 2, '.', '');
                $acumuladoDonut += $porcentaje;
                $fin = number_format($acumuladoDonut, 2, '.', '');
                $segmentosDonut[] = "{$color} {$inicio}% {$fin}%";
            }
        }

        $donutFondo = $segmentosDonut
            ? 'conic-gradient('.implode(', ', $segmentosDonut).')'
            : 'conic-gradient(#e6edf5 0% 100%)';

        $maxTendencia = max(1, (int) collect($analitica['tendencia'])->max('total'));
        $maxCategoria = max(1, (int) collect($analitica['categorias'])->max('total'));
        $maxPrioridad = max(1, (int) collect($analitica['prioridades'])->max('total'));
        $hoy = now()->toDateString();
        $periodosRapidos = [
            'Últimos 7 días' => ['fecha_inicio' => now()->subDays(6)->toDateString(), 'fecha_fin' => $hoy],
            'Últimos 30 días' => ['fecha_inicio' => now()->subDays(29)->toDateString(), 'fecha_fin' => $hoy],
            'Este mes' => ['fecha_inicio' => now()->startOfMonth()->toDateString(), 'fecha_fin' => $hoy],
        ];
    @endphp

    <section class="ts-report-hero mb-4" aria-labelledby="reporteTitulo">
        <div class="ts-report-hero-copy">
            <div class="ts-report-eyebrow">
                <span>Centro de análisis</span>
                <span class="ts-report-access">Administración</span>
            </div>
            <h1 id="reporteTitulo">Reporte general de atención</h1>
            <p>Analiza el volumen de solicitudes, identifica patrones de atención y consulta el expediente de cada incidencia.</p>
            <div class="ts-report-hero-meta">
                <span><svg class="ts-icon ts-icon-inline"><use href="#ts-icon-history"/></svg> {{ $periodoTexto }}</span>
                <span><svg class="ts-icon ts-icon-inline"><use href="#ts-icon-clipboard"/></svg> {{ number_format($total) }} solicitudes consideradas</span>
            </div>
        </div>
        <div class="ts-report-hero-actions">
            <a href="{{ route('admin.reportes.atencion.imprimir', $filtros) }}" class="btn btn-light" target="_blank" rel="noopener">
                <svg class="ts-icon ts-icon-inline"><use href="#ts-icon-activity"/></svg> Vista de impresión
            </a>
            <a href="{{ route('admin.reportes.atencion.exportar', $filtros) }}" class="btn btn-primary">
                <svg class="ts-icon ts-icon-inline"><use href="#ts-icon-chart"/></svg> Exportar CSV
            </a>
            <a href="{{ route('admin.reportes.atencion.exportar.excel', $filtros) }}" class="btn btn-success">
                <i class="bi bi-file-earmark-excel" aria-hidden="true"></i> Exportar Excel
            </a>
        </div>
        <div class="ts-report-hero-orbit" aria-hidden="true"></div>
    </section>

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

    <section class="ts-panel ts-report-filter-panel mb-4" aria-labelledby="filtrosReporteTitulo">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
            <div>
                <span class="ts-report-section-kicker">Refinar resultados</span>
                <h2 class="h5 fw-bold mb-1" id="filtrosReporteTitulo">Filtros del reporte</h2>
                <p class="text-secondary small mb-0">Las métricas y gráficas se actualizan con los mismos criterios.</p>
            </div>
            <span class="ts-report-period-pill"><svg class="ts-icon ts-icon-inline"><use href="#ts-icon-history"/></svg> {{ $periodoTexto }}</span>
        </div>

        <div class="ts-report-quick-periods mb-3" aria-label="Periodos rápidos">
            <span class="small text-secondary fw-semibold">Periodos rápidos:</span>
            @foreach ($periodosRapidos as $etiqueta => $rango)
                <a href="{{ route('admin.reportes.atencion', array_merge($filtros, $rango)) }}" class="ts-report-quick-link">{{ $etiqueta }}</a>
            @endforeach
            <a href="{{ route('admin.reportes.atencion', array_diff_key($filtros, ['fecha_inicio' => true, 'fecha_fin' => true])) }}" class="ts-report-quick-link ts-report-quick-link-muted">Todos los periodos</a>
        </div>

        <form method="GET" action="{{ route('admin.reportes.atencion') }}">
            <div class="row g-3">
                <div class="col-12 col-sm-6 col-xl-3">
                    <label for="fecha_inicio" class="form-label">Fecha inicial</label>
                    <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control" value="{{ $filtros['fecha_inicio'] ?? '' }}">
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <label for="fecha_fin" class="form-label">Fecha final</label>
                    <input type="date" name="fecha_fin" id="fecha_fin" class="form-control" value="{{ $filtros['fecha_fin'] ?? '' }}">
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <label for="estado_id" class="form-label">Estado</label>
                    <select name="estado_id" id="estado_id" class="form-select">
                        <option value="">Todos los estados</option>
                        @foreach ($estados as $estado)
                            <option value="{{ $estado->id }}" @selected((string) ($filtros['estado_id'] ?? '') === (string) $estado->id)>{{ $estado->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <label for="categoria_id" class="form-label">Categoría</label>
                    <select name="categoria_id" id="categoria_id" class="form-select">
                        <option value="">Todas las categorías</option>
                        @foreach ($categorias as $categoria)
                            <option value="{{ $categoria->id }}" @selected((string) ($filtros['categoria_id'] ?? '') === (string) $categoria->id)>{{ $categoria->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <label for="prioridad_id" class="form-label">Prioridad</label>
                    <select name="prioridad_id" id="prioridad_id" class="form-select">
                        <option value="">Todas las prioridades</option>
                        @foreach ($prioridades as $prioridad)
                            <option value="{{ $prioridad->id }}" @selected((string) ($filtros['prioridad_id'] ?? '') === (string) $prioridad->id)>{{ $prioridad->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
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
                <span class="ts-report-section-kicker">Lectura ejecutiva</span>
                <h2 class="h5 fw-bold mb-1" id="resumenReporteTitulo">Resumen de atención</h2>
                <p class="text-secondary small mb-0">Indicadores calculados sobre las solicitudes que coinciden con los filtros.</p>
            </div>
            <span class="ts-report-result-count">{{ number_format($total) }} registros</span>
        </div>

        <div class="row g-3">
            <div class="col-6 col-md-4 col-xxl-2">
                <div class="ts-report-metric ts-report-metric-total h-100" data-report-metric="total" data-value="{{ $total }}">
                    <span class="ts-report-metric-icon"><svg class="ts-icon"><use href="#ts-icon-ticket"/></svg></span>
                    <span class="ts-report-metric-label">Total de solicitudes</span>
                    <strong>{{ number_format($total) }}</strong>
                    <small>Registros encontrados</small>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xxl-2">
                <div class="ts-report-metric ts-report-metric-pending h-100" data-report-metric="pendientes" data-value="{{ $resumen['pendientes'] }}">
                    <span class="ts-report-metric-icon"><svg class="ts-icon"><use href="#ts-icon-alert"/></svg></span>
                    <span class="ts-report-metric-label">Pendientes</span>
                    <strong>{{ number_format($resumen['pendientes']) }}</strong>
                    <small>Sin asignación técnica</small>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xxl-2">
                <div class="ts-report-metric ts-report-metric-progress h-100" data-report-metric="en-atencion" data-value="{{ $resumen['en_atencion'] }}">
                    <span class="ts-report-metric-icon"><svg class="ts-icon"><use href="#ts-icon-activity"/></svg></span>
                    <span class="ts-report-metric-label">En atención</span>
                    <strong>{{ number_format($resumen['en_atencion']) }}</strong>
                    <small>Asignadas o en proceso</small>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xxl-2">
                <div class="ts-report-metric ts-report-metric-done h-100" data-report-metric="finalizadas" data-value="{{ $resumen['finalizados'] }}">
                    <span class="ts-report-metric-icon"><svg class="ts-icon"><use href="#ts-icon-check"/></svg></span>
                    <span class="ts-report-metric-label">Finalizadas</span>
                    <strong>{{ number_format($resumen['finalizados']) }}</strong>
                    <small>Resueltas o cerradas</small>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xxl-2">
                <div class="ts-report-metric ts-report-metric-time h-100" data-report-metric="promedio-resolucion">
                    <span class="ts-report-metric-icon"><svg class="ts-icon"><use href="#ts-icon-activity"/></svg></span>
                    <span class="ts-report-metric-label">Promedio de resolución</span>
                    <strong class="ts-report-metric-duration">{{ $resumen['promedio_resolucion'] }}</strong>
                    <small>Registro hasta resolución</small>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xxl-2">
                <div class="ts-report-metric ts-report-metric-rate h-100" data-report-metric="tasa-finalizacion" data-value="{{ $tasaFinalizacion }}">
                    <span class="ts-report-metric-icon"><svg class="ts-icon"><use href="#ts-icon-chart"/></svg></span>
                    <span class="ts-report-metric-label">Tasa de finalización</span>
                    <strong>{{ $tasaFinalizacion }}%</strong>
                    <small>Solicitudes resueltas o cerradas</small>
                </div>
            </div>
        </div>
    </section>

    <section class="row g-3 mb-4" aria-label="Análisis gráfico de solicitudes">
        <div class="col-12 col-xl-8">
            <article class="ts-report-chart-card h-100">
                <div class="ts-report-card-heading">
                    <div>
                        <span class="ts-report-section-kicker">Comportamiento temporal</span>
                        <h2 class="h5 fw-bold mb-1">Tendencia de solicitudes</h2>
                        <p class="text-secondary small mb-0">Evolución mensual de hasta los 12 meses más recientes del periodo seleccionado.</p>
                    </div>
                    <span class="ts-report-chart-tag">{{ count($analitica['tendencia']) }} meses</span>
                </div>
                @if (collect($analitica['tendencia'])->sum('total') > 0)
                    <div class="ts-report-month-chart" role="img" aria-label="Gráfica de solicitudes registradas por mes">
                        @foreach ($analitica['tendencia'] as $mes)
                            @php $altura = $mes['total'] > 0 ? max(8, (int) round(($mes['total'] / $maxTendencia) * 100)) : 3; @endphp
                            <div class="ts-report-month-column" data-chart-month="{{ $mes['periodo'] }}" aria-label="{{ $mes['etiqueta'] }}: {{ $mes['total'] }} solicitudes">
                                <strong>{{ number_format($mes['total']) }}</strong>
                                <div class="ts-report-month-track"><span style="height: {{ $altura }}%"></span></div>
                                <small>{{ $mes['etiqueta'] }}</small>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="ts-report-chart-empty"><svg class="ts-icon"><use href="#ts-icon-chart"/></svg><strong>Sin actividad en el periodo</strong><span>Cuando existan solicitudes registradas, su evolución aparecerá aquí.</span></div>
                @endif
            </article>
        </div>

        <div class="col-12 col-xl-4">
            <article class="ts-report-chart-card h-100">
                <div class="ts-report-card-heading">
                    <div>
                        <span class="ts-report-section-kicker">Situación actual</span>
                        <h2 class="h5 fw-bold mb-1">Distribución por estado</h2>
                        <p class="text-secondary small mb-0">Participación de cada estado en el reporte.</p>
                    </div>
                </div>
                <div class="ts-report-donut-layout">
                    <div class="ts-report-donut" style="background: {{ $donutFondo }}" role="img" aria-label="Distribución de {{ $total }} solicitudes por estado">
                        <div class="ts-report-donut-center"><strong>{{ number_format($total) }}</strong><span>Solicitudes</span></div>
                    </div>
                    <ul class="ts-report-legend list-unstyled mb-0">
                        @foreach ($resumen['estados'] as $estado)
                            @php $colorEstado = $coloresEstado[$estado['nombre']] ?? '#94a3b8'; @endphp
                            <li data-chart-state="{{ $estado['nombre'] }}">
                                <span class="ts-report-legend-dot" style="background: {{ $colorEstado }}"></span>
                                <span>{{ $estado['nombre'] }}</span>
                                <strong>{{ number_format($estado['total']) }}</strong>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </article>
        </div>

        <div class="col-12 col-lg-6">
            <article class="ts-report-chart-card h-100">
                <div class="ts-report-card-heading">
                    <div>
                        <span class="ts-report-section-kicker">Clasificación</span>
                        <h2 class="h5 fw-bold mb-1">Solicitudes por categoría</h2>
                        <p class="text-secondary small mb-0">Categorías con mayor número de incidencias.</p>
                    </div>
                </div>
                @forelse ($analitica['categorias'] as $categoria)
                    <div class="ts-report-rank-row" data-chart-category="{{ $categoria['nombre'] }}">
                        <span>{{ $categoria['nombre'] }}</span>
                        <div class="ts-report-rank-track"><span style="width: {{ ($categoria['total'] / $maxCategoria) * 100 }}%"></span></div>
                        <strong>{{ number_format($categoria['total']) }}</strong>
                    </div>
                @empty
                    <div class="ts-report-chart-empty compact">No hay categorías para mostrar.</div>
                @endforelse
            </article>
        </div>

        <div class="col-12 col-lg-6">
            <article class="ts-report-chart-card h-100">
                <div class="ts-report-card-heading">
                    <div>
                        <span class="ts-report-section-kicker">Nivel de atención</span>
                        <h2 class="h5 fw-bold mb-1">Solicitudes por prioridad</h2>
                        <p class="text-secondary small mb-0">Distribución de la carga según su prioridad.</p>
                    </div>
                </div>
                @forelse ($analitica['prioridades'] as $prioridad)
                    <div class="ts-report-rank-row ts-report-rank-priority" data-chart-priority="{{ $prioridad['nombre'] }}">
                        <span>{{ $prioridad['nombre'] }}</span>
                        <div class="ts-report-rank-track"><span style="width: {{ ($prioridad['total'] / $maxPrioridad) * 100 }}%"></span></div>
                        <strong>{{ number_format($prioridad['total']) }}</strong>
                    </div>
                @empty
                    <div class="ts-report-chart-empty compact">No hay prioridades para mostrar.</div>
                @endforelse
            </article>
        </div>
    </section>

    <section class="ts-report-insight mb-4" aria-label="Lectura del reporte">
        <span class="ts-report-insight-icon"><svg class="ts-icon"><use href="#ts-icon-info"/></svg></span>
        <div>
            <strong>Lectura del periodo</strong>
            @if ($total > 0)
                <p class="mb-0">Se encontraron <strong>{{ number_format($total) }}</strong> solicitudes; <strong>{{ number_format($resumen['finalizados']) }}</strong> están resueltas o cerradas, lo que representa una tasa de finalización del <strong>{{ $tasaFinalizacion }}%</strong>.</p>
            @else
                <p class="mb-0">No existen solicitudes que coincidan con los criterios seleccionados. Ajusta los filtros para obtener resultados.</p>
            @endif
        </div>
    </section>

    <section class="ts-panel ts-report-table-panel" aria-labelledby="detalleReporteTitulo">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
            <div>
                <span class="ts-report-section-kicker">Consulta individual</span>
                <h2 class="h5 fw-bold mb-1" id="detalleReporteTitulo">Expedientes de atención</h2>
                <p class="text-secondary small mb-0">Abre una solicitud para revisar sus datos completos y la cronología de atención.</p>
            </div>
            <span class="ts-report-result-count">{{ number_format($tickets->total()) }} resultados</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle ts-report-table">
                <thead>
                    <tr>
                        <th scope="col">Folio</th>
                        <th scope="col">Solicitud</th>
                        <th scope="col">Solicitante</th>
                        <th scope="col">Técnico</th>
                        <th scope="col">Equipo</th>
                        <th scope="col">Categoría</th>
                        <th scope="col">Prioridad</th>
                        <th scope="col">Estado</th>
                        <th scope="col">Registro</th>
                        <th scope="col">Expediente</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tickets as $ticket)
                        <tr>
                            <td class="text-nowrap"><strong class="ts-report-folio">{{ $ticket->folio }}</strong></td>
                            <td class="ts-report-subject">{{ $ticket->asunto }}</td>
                            <td><span class="d-block">{{ $ticket->solicitante?->name ?? 'No disponible' }}</span><small class="text-secondary">{{ $ticket->solicitante?->email }}</small></td>
                            <td>{{ $ticket->tecnico?->name ?? 'Sin asignar' }}</td>
                            <td>{{ $ticket->equipo?->codigo ?? '—' }}</td>
                            <td>{{ $ticket->categoria?->nombre ?? '—' }}</td>
                            <td>{{ $ticket->prioridad?->nombre ?? '—' }}</td>
                            <td><span class="ts-report-state-pill">{{ $ticket->estado?->nombre ?? 'No disponible' }}</span></td>
                            <td class="text-nowrap">{{ $ticket->creado_en?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td>
                                <a href="{{ route('admin.reportes.atencion.detalle', array_merge(['ticket' => $ticket->id], $filtros)) }}" class="btn btn-sm btn-outline-primary ts-report-detail-link">
                                    <svg class="ts-icon ts-icon-inline"><use href="#ts-icon-history"/></svg> Ver expediente
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center py-5"><strong class="d-block mb-1">No se encontraron solicitudes.</strong><span class="text-secondary small">Modifica los filtros para consultar otros registros.</span></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($tickets->hasPages())
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-3">
                <small class="text-secondary">Mostrando {{ $tickets->firstItem() }}–{{ $tickets->lastItem() }} de {{ $tickets->total() }} solicitudes.</small>
                {{ $tickets->links() }}
            </div>
        @endif
    </section>
@endsection
