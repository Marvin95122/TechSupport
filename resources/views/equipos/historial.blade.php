@extends('layouts.app')

@section('title', 'Historial del equipo')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <p class="text-uppercase text-secondary small fw-semibold mb-1">Soporte técnico / Equipos</p>
            <h1 class="ts-page-heading">Historial del equipo</h1>
            <p class="text-secondary mb-0">
                Incidencias asociadas con el equipo <strong>{{ $equipo->codigo }}</strong>.
            </p>
        </div>

        @if ($esAdministrador)
            <a href="{{ route('admin.equipos.show', $equipo) }}" class="btn btn-outline-secondary">Volver a la ficha</a>
        @else
            <a href="{{ route('tecnico.asignaciones.index') }}" class="btn btn-outline-secondary">Volver a mis asignaciones</a>
        @endif
    </div>

    <section class="ts-module-card mb-4" aria-label="Datos del equipo consultado">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
            <div>
                <h2 class="h5 fw-bold mb-1">{{ $equipo->tipo }} {{ $equipo->marca }} {{ $equipo->modelo }}</h2>
                <p class="text-secondary mb-0">Código: {{ $equipo->codigo }}</p>
            </div>
            <span class="badge {{ $equipo->activo ? 'text-bg-success' : 'text-bg-secondary' }}">
                {{ $equipo->activo ? 'Equipo activo' : 'Equipo inactivo · historial conservado' }}
            </span>
        </div>

        <dl class="row mb-0">
            <dt class="col-sm-3">Número de serie</dt>
            <dd class="col-sm-9">{{ $equipo->serie ?: 'No registrado' }}</dd>
            <dt class="col-sm-3">Área</dt>
            <dd class="col-sm-9">{{ $equipo->area?->nombre ?? 'Sin área asignada' }}</dd>
            <dt class="col-sm-3">Estado técnico</dt>
            <dd class="col-sm-9">{{ str_replace('_', ' ', ucfirst($equipo->estado)) }}</dd>
        </dl>
    </section>

    <section class="ts-module-card" aria-label="Tickets relacionados con el equipo">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h2 class="h5 fw-bold mb-1">Incidencias relacionadas</h2>
                <p class="text-secondary small mb-0">
                    @if ($esAdministrador)
                        Se muestran los tickets registrados para este equipo.
                    @else
                        Se muestran los tickets de este equipo que están asignados a tu cuenta.
                    @endif
                </p>
            </div>
            <span class="badge text-bg-light">{{ $tickets->total() }} tickets</span>
        </div>

        @if ($tickets->isEmpty())
            <div class="alert alert-light border mb-0">
                No hay tickets relacionados con este equipo para los permisos de tu cuenta.
            </div>
        @else
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Folio</th>
                            <th>Asunto</th>
                            <th>Estado</th>
                            <th>Prioridad</th>
                            @if ($esAdministrador)
                                <th>Solicitante</th>
                                <th>Técnico</th>
                            @endif
                            <th>Fecha</th>
                            <th class="text-end">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tickets as $ticket)
                            <tr>
                                <td class="fw-semibold">{{ $ticket->folio }}</td>
                                <td>{{ $ticket->asunto }}</td>
                                <td>{{ $ticket->estado?->nombre ?? 'No disponible' }}</td>
                                <td>{{ $ticket->prioridad?->nombre ?? 'No disponible' }}</td>
                                @if ($esAdministrador)
                                    <td>{{ $ticket->solicitante?->name ?? 'No disponible' }}</td>
                                    <td>{{ $ticket->tecnico?->name ?? 'Sin asignar' }}</td>
                                @endif
                                <td>{{ $ticket->creado_en?->format('d/m/Y H:i') ?? 'Sin fecha' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('tickets.show', $ticket) }}" class="btn btn-sm btn-outline-primary">Ver ticket</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $tickets->links() }}
            </div>
        @endif
    </section>
@endsection
