@extends('layouts.app')

@section('title', 'Registrar equipo')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <p class="text-uppercase text-secondary small fw-semibold mb-1">Administración / Equipos</p>
            <h1 class="ts-page-heading">Registrar equipo tecnológico</h1>
            <p class="text-secondary mb-0">Identifica el recurso y, si corresponde, asígnale un área y un responsable.</p>
        </div>
        <a href="{{ route('admin.equipos.index') }}" class="btn btn-outline-secondary">Volver a equipos</a>
    </div>

    @if (session('status'))
        <div class="alert alert-success" role="status">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">Revisa los campos señalados antes de guardar.</div>
    @endif

    <div class="row g-4">
        <div class="col-12 col-xl-8">
            <section class="ts-module-card" aria-label="Formulario de equipo">
                <form action="{{ route('admin.equipos.store') }}" method="POST">
                    @csrf
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="codigo" class="form-label fw-semibold">Código único *</label>
                            <input id="codigo" name="codigo" class="form-control @error('codigo') is-invalid @enderror"
                                   value="{{ old('codigo') }}" required maxlength="50" autocomplete="off">
                            @error('codigo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="tipo" class="form-label fw-semibold">Tipo de equipo *</label>
                            <input id="tipo" name="tipo" class="form-control @error('tipo') is-invalid @enderror"
                                   value="{{ old('tipo') }}" required maxlength="100" placeholder="Ej. Computadora">
                            @error('tipo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="marca" class="form-label fw-semibold">Marca *</label>
                            <input id="marca" name="marca" class="form-control @error('marca') is-invalid @enderror"
                                   value="{{ old('marca') }}" required maxlength="100">
                            @error('marca') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="modelo" class="form-label fw-semibold">Modelo *</label>
                            <input id="modelo" name="modelo" class="form-control @error('modelo') is-invalid @enderror"
                                   value="{{ old('modelo') }}" required maxlength="100">
                            @error('modelo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="serie" class="form-label fw-semibold">Número de serie (opcional)</label>
                            <input id="serie" name="serie" class="form-control @error('serie') is-invalid @enderror"
                                   value="{{ old('serie') }}" maxlength="100">
                            @error('serie') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="estado" class="form-label fw-semibold">Estado técnico *</label>
                            <select id="estado" name="estado" class="form-select @error('estado') is-invalid @enderror" required>
                                <option value="">Seleccionar estado</option>
                                <option value="operativo" @selected(old('estado') === 'operativo')>Operativo</option>
                                <option value="mantenimiento" @selected(old('estado') === 'mantenimiento')>Mantenimiento</option>
                                <option value="fuera_de_servicio" @selected(old('estado') === 'fuera_de_servicio')>Fuera de servicio</option>
                            </select>
                            @error('estado') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="area_id" class="form-label fw-semibold">Área (opcional)</label>
                            <select id="area_id" name="area_id" class="form-select @error('area_id') is-invalid @enderror">
                                <option value="">Sin área asignada</option>
                                @foreach ($areas as $area)
                                    <option value="{{ $area->id }}" @selected((string) old('area_id') === (string) $area->id)>{{ $area->nombre }}</option>
                                @endforeach
                            </select>
                            @error('area_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="responsable_id" class="form-label fw-semibold">Responsable (opcional)</label>
                            <select id="responsable_id" name="responsable_id" class="form-select @error('responsable_id') is-invalid @enderror">
                                <option value="">Sin responsable asignado</option>
                                @foreach ($responsables as $responsable)
                                    <option value="{{ $responsable->id }}" @selected((string) old('responsable_id') === (string) $responsable->id)>{{ $responsable->name }} ({{ $responsable->email }})</option>
                                @endforeach
                            </select>
                            @error('responsable_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <hr class="my-4">
                    <div class="d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary">Guardar equipo</button>
                        <a href="{{ route('admin.equipos.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </section>
        </div>
        <div class="col-12 col-xl-4">
            <section class="ts-module-card" aria-label="Administración de áreas">
                <h2 class="h5 ts-module-title">Registrar área</h2>
                <p class="text-secondary small">Si no aparece el área necesaria, regístrala aquí y después selecciónala en el formulario de equipo.</p>
                <form action="{{ route('admin.areas.store') }}" method="POST">
                    @csrf
                    <label for="nombre" class="form-label fw-semibold">Nombre del área</label>
                    <input id="nombre" name="nombre" value="{{ old('nombre') }}"
                           class="form-control @error('nombre') is-invalid @enderror" maxlength="150" required>
                    @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <button type="submit" class="btn btn-outline-primary mt-3">Agregar área</button>
                </form>
                <p class="text-secondary small mt-3 mb-0">No se crean áreas ficticias automáticamente. Registra únicamente las aprobadas para el proyecto.</p>
            </section>
        </div>
    </div>
@endsection
