@extends('layouts.app')
@section('title', 'Mis asignaciones')
@section('content')
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
        <div>
            <p class="text-uppercase text-secondary small fw-semibold mb-1">Soporte técnico / Asignaciones</p>
            <h1 class="ts-page-heading">Mis asignaciones</h1>
            <p class="text-secondary mb-0">Consulta los tickets que te han asignado y accede al detalle de cada atención.</p>
        </div>
    </div>

    <section class="ts-module-card" aria-label="Tickets asignados al técnico">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">Folio</th>
                        <th scope="col">Prioridad</th>
                        <th scope="col">Estado</th>
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
                            <td>{{ $ticket->prioridad?->nombre ?? 'No disponible' }}</td>
                            <td>
                                <span class="badge text-bg-{{ $estadoColor }}">
                                    {{ $ticket->estado?->nombre ?? 'No disponible' }}
                                </span>
                            </td>
                            <td>
                                <a class="btn btn-sm btn-outline-primary text-nowrap"
                                   href="{{ route('tickets.show', $ticket) }}">
                                    Ver detalle
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-secondary py-5">
                                No tienes tickets asignados actualmente.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($tickets->hasPages())
            <div class="mt-4">{{ $tickets->links('pagination::bootstrap-5') }}</div>
        @endif

        <p class="small text-secondary mt-3 mb-0">
            Se muestran únicamente las solicitudes asignadas a tu cuenta. Para consultar o actualizar una atención, abre su detalle.
        </p>
    </section>
@endsection
