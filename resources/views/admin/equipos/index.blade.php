@extends('layouts.app')

@section('title', 'Equipos tecnológicos')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <p class="text-uppercase text-secondary small fw-semibold mb-1">Administración / Equipos</p>
            <h1 class="ts-page-heading">Equipos tecnológicos</h1>
            <p class="text-secondary mb-0">Consulta el inventario activo y registra los equipos que requieren soporte.</p>
        </div>
        <a href="{{ route('admin.equipos.create') }}" class="btn btn-primary">Registrar equipo</a>
    </div>

    <section class="ts-module-card" aria-label="Inventario de equipos">
        @if (session('status'))
            <div class="alert alert-success" role="status">{{ session('status') }}</div>
        @endif

        <form method="GET" action="{{ route('admin.equipos.index') }}" class="row g-2 align-items-end mb-4">
            <div class="col-12 col-md-7 col-lg-5">
                <label class="form-label fw-semibold" for="buscar">Buscar equipo</label>
                <input type="search" class="form-control" name="buscar" id="buscar"
                       maxlength="100" value="{{ $buscar }}" placeholder="Código, tipo, marca o modelo">
            </div>
            <div class="col-auto"><button type="submit" class="btn btn-primary">Buscar</button></div>
            @if ($buscar !== '')
                <div class="col-auto"><a class="btn btn-outline-secondary" href="{{ route('admin.equipos.index') }}">Limpiar</a></div>
            @endif
        </form>

        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead>
                    <tr>
                        <th scope="col">Código</th><th scope="col">Tipo</th>
                        <th scope="col">Marca / modelo</th><th scope="col">Estado técnico</th>
                        <th scope="col">Área</th><th scope="col">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($equipos as $equipo)
                        <tr>
                            <td class="fw-semibold">{{ $equipo->codigo }}</td>
                            <td>{{ $equipo->tipo }}</td>
                            <td>{{ $equipo->marca }} / {{ $equipo->modelo }}</td>
                            <td>
                                @if ($equipo->estado === 'operativo')
                                    <span class="badge text-bg-success">Operativo</span>
                                @elseif ($equipo->estado === 'mantenimiento')
                                    <span class="badge text-bg-warning">Mantenimiento</span>
                                @else
                                    <span class="badge text-bg-secondary">Fuera de servicio</span>
                                @endif
                            </td>
                            <td>{{ $equipo->area?->nombre ?? 'Sin área' }}</td>
                            <td><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.equipos.show', $equipo) }}">Ver ficha</a>
                                <a class="btn btn-sm btn-outline-secondary ms-1" href="{{ route('admin.equipos.edit', $equipo) }}">Editar</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-secondary py-4">No hay equipos activos que coincidan con la búsqueda.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($equipos->hasPages())
            <div class="mt-3">{{ $equipos->links('pagination::bootstrap-5') }}</div>
        @endif
        <p class="text-secondary small mt-3 mb-0">Se muestran únicamente los equipos activos. Los desactivados permanecen en la base de datos para conservar su historial.</p>
    </section>
@endsection
