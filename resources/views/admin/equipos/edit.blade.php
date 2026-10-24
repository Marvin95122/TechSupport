@extends('layouts.app')

@section('title', 'Editar equipo tecnológico')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <p class="text-uppercase text-secondary small fw-semibold mb-1">Administración / Equipos</p>
            <h1 class="ts-page-heading">Editar equipo: {{ $equipo->codigo }}</h1>
            <p class="text-secondary mb-0">Actualiza los datos del inventario y sus asignaciones.</p>
        </div>
        <a href="{{ route('admin.equipos.show', $equipo) }}" class="btn btn-outline-secondary">Volver a la ficha</a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">Revisa los campos señalados antes de guardar.</div>
    @endif

    <div class="row">
        <div class="col-12 col-xl-9">
            <section class="ts-module-card" aria-label="Edición de equipo">
                <form action="{{ route('admin.equipos.update', $equipo) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="codigo" class="form-label fw-semibold">Código único *</label>
                            <input id="codigo" name="codigo" required maxlength="50" autocomplete="off"
                                class="form-control @error('codigo') is-invalid @enderror"
                                value="{{ old('codigo', $equipo->codigo) }}">
                            @error('codigo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="tipo" class="form-label fw-semibold">Tipo de equipo *</label>
                            <input id="tipo" name="tipo" required maxlength="100"
                                class="form-control @error('tipo') is-invalid @enderror"
                                value="{{ old('tipo', $equipo->tipo) }}">
                            @error('tipo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="marca" class="form-label fw-semibold">Marca *</label>
                            <input id="marca" name="marca" required maxlength="100"
                                class="form-control @error('marca') is-invalid @enderror"
                                value="{{ old('marca', $equipo->marca) }}">
                            @error('marca') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="modelo" class="form-label fw-semibold">Modelo *</label>
                            <input id="modelo" name="modelo" required maxlength="100"
                                class="form-control @error('modelo') is-invalid @enderror"
                                value="{{ old('modelo', $equipo->modelo) }}">
                            @error('modelo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="serie" class="form-label fw-semibold">Número de serie (opcional)</label>
                            <input id="serie" name="serie" maxlength="100"
                                class="form-control @error('serie') is-invalid @enderror"
                                value="{{ old('serie', $equipo->serie) }}">
                            @error('serie') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="estado" class="form-label fw-semibold">Estado técnico *</label>
                            <select id="estado" name="estado" required class="form-select @error('estado') is-invalid @enderror">
                                <option value="">Seleccionar estado</option>
                                <option value="operativo" @selected(old('estado', $equipo->estado) === 'operativo')>Operativo</option>
                                <option value="mantenimiento" @selected(old('estado', $equipo->estado) === 'mantenimiento')>Mantenimiento</option>
                                <option value="fuera_de_servicio" @selected(old('estado', $equipo->estado) === 'fuera_de_servicio')>Fuera de servicio</option>
                            </select>
                            @error('estado') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="area_id" class="form-label fw-semibold">Área (opcional)</label>
                            <select id="area_id" name="area_id" class="form-select @error('area_id') is-invalid @enderror">
                                <option value="">Sin área asignada</option>
                                @foreach ($areas as $area)
                                    <option value="{{ $area->id }}" @selected((string) old('area_id', $equipo->area_id) === (string) $area->id)>{{ $area->nombre }}</option>
                                @endforeach
                            </select>
                            @error('area_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="responsable_id" class="form-label fw-semibold">Responsable (opcional)</label>
                            <select id="responsable_id" name="responsable_id" class="form-select @error('responsable_id') is-invalid @enderror">
                                <option value="">Sin responsable asignado</option>
                                @foreach ($responsables as $responsable)
                                    <option value="{{ $responsable->id }}" @selected((string) old('responsable_id', $equipo->responsable_id) === (string) $responsable->id)>
                                        {{ $responsable->name }} ({{ $responsable->email }})
                                    </option>
                                @endforeach
                            </select>
                            @error('responsable_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text">Únicamente se permiten responsables con cuenta activa.</div>
                        </div>
                    </div>
                    <hr class="my-4">
                    <div class="d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary">Guardar cambios</button>
                        <a class="btn btn-outline-secondary" href="{{ route('admin.equipos.show', $equipo) }}">Cancelar</a>
                    </div>
                </form>
            </section>
        </div>
    </div>
@endsection
