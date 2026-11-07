@extends('layouts.app')
@section('title', $esAdministrador ? 'Consultas de tickets' : ($esTecnico ? 'Tickets asignados' : 'Mis tickets'))
@section('content')
    @php
        $titulo = $esAdministrador ? 'Tickets registrados' : ($esTecnico ? 'Tickets asignados' : 'Mis tickets');
        $descripcion = $esAdministrador
            ? 'Localiza solicitudes y revisa el estado general de atención.'
            : ($esTecnico
                ? 'Consulta únicamente las solicitudes asignadas a tu cuenta.'
                : 'Consulta las solicitudes que has registrado y su estado actual.');
        $columnas = $esAdministrador ? 10 : ($esTecnico ? 9 : 7);
        $etiquetaBuscar = $esAdministrador
            ? 'Buscar por folio, solicitante, técnico o equipo'
            : ($esTecnico ? 'Buscar por folio, solicitante o equipo' : 'Buscar por folio o asunto');
        $placeholderBuscar = $esAdministrador
            ? 'Folio, nombre/correo, técnico o código de equipo'
            : ($esTecnico ? 'Folio, nombre del solicitante o equipo' : 'Escribe el folio o asunto');
    @endphp

    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
        <div>
            <p class="text-uppercase text-secondary small fw-semibold mb-1">Soporte técnico / Consultas</p>
            <h1 class="ts-page-heading">{{ $titulo }}</h1>
            <p class="text-secondary mb-0">{{ $descripcion }}</p>
        </div>
        @if ($esSolicitante)
            <a class="btn btn-primary" href="{{ route('tickets.create') }}">+ Nueva solicitud</a>
        @endif
    </div>

    <section class="ts-module-card" aria-label="Consulta y filtros de tickets">
        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                <strong class="d-block mb-1">No se pudo aplicar la consulta.</strong>
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form class="ts-filter-panel p-3 p-lg-4 rounded-3 mb-4" action="{{ route('tickets.index') }}" method="GET" role="search">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <div>
                    <h2 class="h6 fw-bold mb-1">Buscar y filtrar solicitudes</h2>
                    <p class="small text-secondary mb-0">Puedes combinar varios criterios en una misma consulta.</p>
                </div>
                <span class="badge text-bg-light border text-secondary">{{ number_format($tickets->total()) }} resultados</span>
            </div>

            <div class="row g-3">
                <div class="col-12 col-lg-6">
                    <label for="buscar" class="form-label">{{ $etiquetaBuscar }}</label>
                    <input id="buscar" name="buscar" type="search" class="form-control"
                           value="{{ $buscar }}" maxlength="120"
                           placeholder="{{ $placeholderBuscar }}">
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <label for="categoria" class="form-label">Categoría</label>
                    <select id="categoria" name="categoria" class="form-select">
                        <option value="">Todas las categorías</option>
                        @foreach ($categorias as $opcion)
                            <option value="{{ $opcion->id }}" @selected($categoria === (string) $opcion->id)>{{ $opcion->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <label for="prioridad" class="form-label">Prioridad</label>
                    <select id="prioridad" name="prioridad" class="form-select">
                        <option value="">Todas las prioridades</option>
                        @foreach ($prioridades as $opcion)
                            <option value="{{ $opcion->id }}" @selected($prioridad === (string) $opcion->id)>{{ $opcion->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <label for="estado" class="form-label">Estado</label>
                    <select id="estado" name="estado" class="form-select">
                        <option value="">Todos los estados</option>
                        @foreach ($estados as $opcion)
                            <option value="{{ $opcion->id }}" @selected($estado === (string) $opcion->id)>{{ $opcion->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <label for="desde" class="form-label">Desde</label>
                    <input id="desde" name="desde" type="date" class="form-control" value="{{ $desde }}">
                    @error('desde') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <label for="hasta" class="form-label">Hasta</label>
                    <input id="hasta" name="hasta" type="date" class="form-control" value="{{ $hasta }}">
                    @error('hasta') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
                <div class="col-12 col-lg-3 d-flex align-items-end gap-2">
                    <button class="btn btn-primary flex-grow-1" type="submit">Aplicar filtros</button>
                    <a href="{{ route('tickets.index') }}" class="btn btn-outline-secondary">Limpiar</a>
                </div>
            </div>
        </form>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h2 class="h6 fw-bold mb-0">Resultados de la consulta</h2>
            <span class="small text-secondary">Página {{ $tickets->currentPage() }} de {{ max(1, $tickets->lastPage()) }}</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">Folio</th>
                        <th scope="col">Asunto</th>
                        @if ($esAdministrador || $esTecnico)
                            <th scope="col">Solicitante</th>
                            <th scope="col">Equipo</th>
                        @endif
                        @if ($esAdministrador)
                            <th scope="col">Técnico</th>
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
                        @php
                            $estadoColor = match ($ticket->estado?->nombre) {
                                'Pendiente' => 'warning',
                                'Asignado' => 'secondary',
                                'En proceso' => 'info',
                                'Resuelto' => 'success',
                                'Cerrado' => 'dark',
                                default => 'secondary',
                            };
                        @endphp
                        <tr>
                            <td class="text-nowrap small fw-semibold">{{ $ticket->folio }}</td>
                            <td class="ts-ticket-subject">{{ $ticket->asunto }}</td>
                            @if ($esAdministrador || $esTecnico)
                                <td>{{ $ticket->solicitante?->name ?? 'No disponible' }}</td>
                                <td class="text-nowrap">{{ $ticket->equipo?->codigo ?? 'Sin equipo' }}</td>
                            @endif
                            @if ($esAdministrador)
                                <td>{{ $ticket->tecnico?->name ?? 'Sin asignar' }}</td>
                            @endif
                            <td>{{ $ticket->categoria?->nombre ?? 'No disponible' }}</td>
                            <td>{{ $ticket->prioridad?->nombre ?? 'No disponible' }}</td>
                            <td><span class="badge text-bg-{{ $estadoColor }}">{{ $ticket->estado?->nombre ?? 'No disponible' }}</span></td>
                            <td class="text-nowrap small">{{ $ticket->creado_en?->format('d/m/Y H:i') ?? 'Sin fecha' }}</td>
                            <td><a class="btn btn-sm btn-outline-primary text-nowrap" href="{{ route('tickets.show', $ticket) }}">Ver detalle</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $columnas }}" class="text-center text-secondary py-5">
                            <strong class="d-block mb-1">No encontramos solicitudes</strong>
                            Ajusta los criterios o limpia los filtros para consultar otros tickets.
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($tickets->hasPages())
            <div class="mt-4">{{ $tickets->links('pagination::bootstrap-5') }}</div>
        @endif
        <p class="small text-secondary mt-3 mb-0">Los resultados respetan el rol y los permisos de la cuenta autenticada. Los filtros se conservan al cambiar de página.</p>
    </section>
@endsection
