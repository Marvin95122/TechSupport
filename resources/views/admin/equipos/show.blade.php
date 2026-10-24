@extends('layouts.app')

@section('title', 'Detalle de equipo')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <p class="text-uppercase text-secondary small fw-semibold mb-1">Administración / Equipos</p>
            <h1 class="ts-page-heading">Ficha técnica: {{ $equipo->codigo }}</h1>
            <p class="text-secondary mb-0">Información registrada del recurso tecnológico.</p>
        </div>
        <a href="{{ route('admin.equipos.index') }}" class="btn btn-outline-secondary">Volver al listado</a>
    </div>

    @if (session('status'))
        <div class="alert alert-success" role="status">{{ session('status') }}</div>
    @endif
    <section class="ts-module-card">
        <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-3">
            <h2 class="h5 ts-module-title mb-0">Datos del equipo</h2>
            <span class="badge {{ $equipo->activo ? 'text-bg-success' : 'text-bg-danger' }}">
                {{ $equipo->activo ? 'Activo' : 'Inactivo' }}
            </span>
        </div>
        <dl class="row mb-0">
            <dt class="col-sm-4 py-2">Código</dt><dd class="col-sm-8 py-2">{{ $equipo->codigo }}</dd>
            <dt class="col-sm-4 py-2">Tipo</dt><dd class="col-sm-8 py-2">{{ $equipo->tipo }}</dd>
            <dt class="col-sm-4 py-2">Marca</dt><dd class="col-sm-8 py-2">{{ $equipo->marca }}</dd>
            <dt class="col-sm-4 py-2">Modelo</dt><dd class="col-sm-8 py-2">{{ $equipo->modelo }}</dd>
            <dt class="col-sm-4 py-2">Número de serie</dt><dd class="col-sm-8 py-2">{{ $equipo->serie ?: 'No registrado' }}</dd>
            <dt class="col-sm-4 py-2">Estado técnico</dt>
            <dd class="col-sm-8 py-2">
                @if ($equipo->estado === 'operativo') Operativo
                @elseif ($equipo->estado === 'mantenimiento') Mantenimiento
                @elseif ($equipo->estado === 'fuera_de_servicio') Fuera de servicio
                @else {{ $equipo->estado }} @endif
            </dd>
            <dt class="col-sm-4 py-2">Área</dt><dd class="col-sm-8 py-2">{{ $equipo->area?->nombre ?? 'Sin área asignada' }}</dd>
            <dt class="col-sm-4 py-2">Responsable</dt><dd class="col-sm-8 py-2">{{ $equipo->responsable?->name ?? 'Sin responsable asignado' }}</dd>
        </dl>
        <p class="text-secondary small mt-4 mb-0">La edición y la baja lógica estarán disponibles en el Checkpoint C2. El registro conservará su identificador para los futuros tickets.</p>
    </section>
@endsection
