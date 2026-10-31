@extends('layouts.app')
@section('title', $esAdministrador ? 'Tickets registrados' : 'Mis tickets')
@section('content')
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
        <div>
            <p class="text-uppercase text-secondary small fw-semibold mb-1">Soporte técnico / Solicitudes</p>
            <h1 class="ts-page-heading">{{ $esAdministrador ? 'Tickets registrados' : 'Mis tickets' }}</h1>
            <p class="text-secondary mb-0">
                {{ $esAdministrador ? 'Consulta las solicitudes recibidas y su información actual.' : 'Consulta las solicitudes que has registrado y su estado actual.' }}
            </p>
        </div>
        @unless ($esAdministrador)
            <a class="btn btn-primary" href="{{ route('tickets.create') }}">+ Nueva solicitud</a>
        @endunless
    </div>

    <section class="ts-module-card" aria-label="Listado de tickets">
        <form class="row g-3 align-items-end mb-4" action="{{ route('tickets.index') }}" method="GET" role="search">
            <div class="col-12 col-lg-6">
                <label for="buscar" class="form-label fw-semibold">Buscar por folio o asunto</label>
                <input id="buscar" name="buscar" type="search" class="form-control"
                       value="{{ $buscar }}" maxlength="120" placeholder="Escribe un folio o asunto">
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <label for="estado" class="form-label fw-semibold">Estado</label>
                <select id="estado" name="estado" class="form-select">
                    <option value="">Todos los estados</option>
                    @foreach ($estados as $opcion)
                        <option value="{{ $opcion->id }}" @selected((string) $estado === (string) $opcion->id)>{{ $opcion->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-sm-6 col-lg-3 d-flex gap-2">
                <button class="btn btn-primary" type="submit">Buscar</button>
                <a href="{{ route('tickets.index') }}" class="btn btn-outline-secondary">Limpiar</a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">Folio</th>
                        <th scope="col">Asunto</th>
                        @if ($esAdministrador)
                            <th scope="col">Solicitante</th>
                        @endif
                        <th scope="col">Categoría</th>
                        <th scope="col">Prioridad</th>
                        <th scope="col">Estado</th>
                        <th scope="col">Registro</th>
                        <th scope="col">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tickets as $ticket)
                        <tr>
                            <td class="text-nowrap small fw-semibold">{{ $ticket->folio }}</td>
                            <td>{{ $ticket->asunto }}</td>
                            @if ($esAdministrador)
                                <td>{{ $ticket->solicitante?->name ?? 'No disponible' }}</td>
                            @endif
                            <td>{{ $ticket->categoria?->nombre ?? 'No disponible' }}</td>
                            <td>{{ $ticket->prioridad?->nombre ?? 'No disponible' }}</td>
                            <td><span class="badge text-bg-{{ $ticket->estado?->nombre === 'Pendiente' ? 'warning' : 'secondary' }}">{{ $ticket->estado?->nombre ?? 'No disponible' }}</span></td>
                            <td class="text-nowrap small">{{ $ticket->creado_en?->format('d/m/Y H:i') ?? 'Sin fecha' }}</td>
                            <td><a class="btn btn-sm btn-outline-primary text-nowrap" href="{{ route('tickets.show', $ticket) }}">Ver detalle</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $esAdministrador ? 8 : 7 }}" class="text-center text-secondary py-5">
                            No hay solicitudes que coincidan con tu consulta.
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($tickets->hasPages())
            <div class="mt-4">{{ $tickets->links('pagination::bootstrap-5') }}</div>
        @endif
        <p class="small text-secondary mt-3 mb-0">Se muestran datos reales. La asignación y el seguimiento técnico se desarrollarán en el Sprint 5.</p>
    </section>
@endsection
