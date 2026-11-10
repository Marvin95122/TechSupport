<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reporte general de atención | TechSupport</title>
    <style>
        :root { color-scheme: light; font-family: Arial, Helvetica, sans-serif; color: #193653; }
        * { box-sizing: border-box; }
        body { margin: 0; padding: 28px; background: #eef2f7; }
        .report-page { max-width: 1500px; margin: 0 auto; padding: 34px; background: #fff; border: 1px solid #dbe4ef; }
        .report-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 24px; padding-bottom: 22px; border-bottom: 2px solid #1d4269; }
        .brand { color: #2563d8; font-size: 13px; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
        h1 { margin: 8px 0; font-size: 27px; color: #173653; }
        .muted { color: #64778d; font-size: 12px; }
        .generated { text-align: right; white-space: nowrap; }
        .filters { display: flex; flex-wrap: wrap; gap: 8px 18px; margin: 18px 0; padding: 12px 14px; background: #f4f7fb; border: 1px solid #dce6f1; font-size: 12px; }
        .metrics { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 12px; margin: 20px 0; }
        .metric { padding: 14px; border: 1px solid #dce6f1; border-radius: 8px; }
        .metric span { display: block; color: #64778d; font-size: 11px; }
        .metric strong { display: block; margin-top: 6px; font-size: 21px; color: #1b3e60; }
        h2 { margin: 22px 0 10px; font-size: 16px; }
        table { width: 100%; border-collapse: collapse; font-size: 10px; }
        th { padding: 9px 7px; text-align: left; color: #284764; background: #edf3fa; border: 1px solid #dce6f1; }
        td { padding: 8px 7px; vertical-align: top; border: 1px solid #e1e8f0; overflow-wrap: anywhere; }
        tbody tr:nth-child(even) { background: #fafcff; }
        .no-results { padding: 24px; text-align: center; color: #64778d; border: 1px solid #dce6f1; }
        .actions { display: flex; justify-content: flex-end; gap: 8px; margin: 0 auto 14px; max-width: 1500px; }
        .actions button { padding: 10px 16px; border: 0; border-radius: 7px; background: #2563d8; color: #fff; font-weight: 700; cursor: pointer; }
        .actions a { padding: 10px 16px; border: 1px solid #c8d5e5; border-radius: 7px; color: #284764; text-decoration: none; background: #fff; }
        @media (max-width: 900px) {
            body { padding: 10px; }
            .report-page { padding: 18px; overflow-x: auto; }
            .metrics { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .report-header { flex-direction: column; }
            .generated { text-align: left; }
        }
        @page { size: landscape; margin: 12mm; }
        @media print {
            body { padding: 0; background: #fff; }
            .report-page { max-width: none; padding: 0; border: 0; }
            .actions { display: none !important; }
            .metrics, .filters { break-inside: avoid; }
            thead { display: table-header-group; }
            tr { break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="actions">
        <a href="{{ route('admin.reportes.atencion', $filtros) }}">Volver al reporte</a>
        <button type="button" onclick="window.print()">Imprimir / Guardar PDF</button>
    </div>

    <main class="report-page">
        <header class="report-header">
            <div>
                <div class="brand">TechSupport · Centro de soporte</div>
                <h1>Reporte general de atención</h1>
                <div class="muted">Periodo de registro: {{ $periodoTexto }}</div>
            </div>
            <div class="generated muted">
                <strong>Fecha de generación</strong><br>
                {{ now()->format('d/m/Y H:i') }}<br>
                <strong>{{ number_format($resumen['total']) }} solicitudes</strong>
            </div>
        </header>

        <div class="filters">
            <strong>Filtros aplicados:</strong>
            @forelse ($filtrosTexto as $etiqueta => $valor)
                <span>{{ $etiqueta }}: {{ $valor }}</span>
            @empty
                <span>Sin filtros adicionales</span>
            @endforelse
        </div>

        <section class="metrics" aria-label="Indicadores generales">
            <div class="metric"><span>Total de solicitudes</span><strong>{{ number_format($resumen['total']) }}</strong></div>
            <div class="metric"><span>Pendientes</span><strong>{{ number_format($resumen['pendientes']) }}</strong></div>
            <div class="metric"><span>En atención</span><strong>{{ number_format($resumen['en_atencion']) }}</strong></div>
            <div class="metric"><span>Finalizadas</span><strong>{{ number_format($resumen['finalizados']) }}</strong></div>
            <div class="metric"><span>Promedio de resolución</span><strong>{{ $resumen['promedio_resolucion'] }}</strong></div>
        </section>

        <h2>Distribución por estado</h2>
        <table>
            <thead><tr><th>Estado</th><th>Total de solicitudes</th></tr></thead>
            <tbody>
                @foreach ($resumen['estados'] as $estado)
                    <tr><td>{{ $estado['nombre'] }}</td><td>{{ number_format($estado['total']) }}</td></tr>
                @endforeach
            </tbody>
        </table>

        <h2>Detalle de solicitudes</h2>
        @if ($tickets->isEmpty())
            <div class="no-results">No se encontraron solicitudes para los filtros seleccionados.</div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Folio</th>
                        <th>Asunto</th>
                        <th>Solicitante</th>
                        <th>Técnico</th>
                        <th>Equipo</th>
                        <th>Categoría</th>
                        <th>Prioridad</th>
                        <th>Estado</th>
                        <th>Registro</th>
                        <th>Asignación</th>
                        <th>Resolución</th>
                        <th>Cierre</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($tickets as $ticket)
                        <tr>
                            <td>{{ $ticket->folio }}</td>
                            <td>{{ $ticket->asunto }}</td>
                            <td>{{ $ticket->solicitante?->name ?? 'No disponible' }}</td>
                            <td>{{ $ticket->tecnico?->name ?? 'Sin asignar' }}</td>
                            <td>{{ $ticket->equipo?->codigo ?? 'Sin equipo' }}</td>
                            <td>{{ $ticket->categoria?->nombre ?? '—' }}</td>
                            <td>{{ $ticket->prioridad?->nombre ?? '—' }}</td>
                            <td>{{ $ticket->estado?->nombre ?? 'No disponible' }}</td>
                            <td>{{ $ticket->creado_en?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td>{{ $ticket->asignado_en?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td>{{ $ticket->resuelto_en?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td>{{ $ticket->cerrado_en?->format('d/m/Y H:i') ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </main>
</body>
</html>
