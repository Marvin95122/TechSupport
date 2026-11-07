@extends('layouts.app')
@section('title', 'Historial de atención')
@section('content')
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
        <div>
            <p class="text-uppercase text-secondary small fw-semibold mb-1">Soporte técnico / Historial</p>
            <h1 class="ts-page-heading">Historial de atención</h1>
            <p class="text-secondary mb-0">
                {{ $esAdministrador ? 'Consulta las cronologías de todas las solicitudes.' : 'Consulta únicamente el historial de los tickets asignados a tu cuenta.' }}
            </p>
        </div>
        <a href="{{ route('tickets.index') }}" class="btn btn-outline-secondary">Volver a consultas</a>
    </div>

    <section class="ts-module-card" aria-label="Listado de historiales de atención">
        <form action="{{ route('historial.index') }}" method="GET" class="row g-2 align-items-end mb-4" role="search">
            <div class="col-12 col-md-7 col-lg-6">
                <label for="buscar" class="form-label">Buscar en el historial</label>
                <input id="buscar" name="buscar" type="search" class="form-control"
                       maxlength="120" value="{{ $buscar }}" placeholder="Folio, asunto, solicitante o equipo">
            </div>
            <div class="col-auto"><button class="btn btn-primary" type="submit">Buscar</button></div>
            @if ($buscar !== '')
                <div class="col-auto"><a class="btn btn-outline-secondary" href="{{ route('historial.index') }}">Limpiar</a></div>
            @endif
        </form>

        <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
            <h2 class="h6 fw-bold mb-0">Tickets con cronología disponible</h2>
            <span class="badge text-bg-light border text-secondary">{{ number_format($tickets->total()) }} registros</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">Folio</th>
                        <th scope="col">Asunto</th>
                        <th scope="col">Solicitante</th>
                        <th scope="col">Equipo</th>
                        @if ($esAdministrador)
                            <th scope="col">Técnico</th>
                        @endif
                        <th scope="col">Estado</th>
                        <th scope="col">Eventos</th>
                        <th scope="col">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tickets as $ticket)
                        <tr>
                            <td class="text-nowrap small fw-semibold">{{ $ticket->folio }}</td>
                            <td>{{ $ticket->asunto }}</td>
                            <td>{{ $ticket->solicitante?->name ?? 'No disponible' }}</td>
                            <td>{{ $ticket->equipo?->codigo ?? 'Sin equipo' }}</td>
                            @if ($esAdministrador)
                                <td>{{ $ticket->tecnico?->name ?? 'Sin asignar' }}</td>
                            @endif
                            <td>{{ $ticket->estado?->nombre ?? 'No disponible' }}</td>
                            <td>
                                <span class="badge text-bg-light border text-secondary">
                                    {{ 1 + $ticket->cambios_estado_count + $ticket->observaciones_count + $ticket->soluciones_count }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('tickets.show', $ticket) }}" class="btn btn-sm btn-outline-primary text-nowrap">Ver cronología</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $esAdministrador ? 8 : 7 }}" class="text-center text-secondary py-5">
                            No hay historiales que coincidan con la búsqueda.
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($tickets->hasPages())
            <div class="mt-4">{{ $tickets->links('pagination::bootstrap-5') }}</div>
        @endif
        <p class="small text-secondary mt-3 mb-0">Cada evento se consulta en la cronología del ticket. No se modifican ni reemplazan los registros originales.</p>
    </section>
@endsection
