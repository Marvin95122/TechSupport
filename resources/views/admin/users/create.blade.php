@extends('layouts.app')

@section('title', 'Registrar usuario')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <p class="text-uppercase text-secondary small fw-semibold mb-1">Administración / Usuarios</p>
            <h1 class="ts-page-heading">Registrar usuario</h1>
            <p class="text-secondary mb-0">Completa los datos para crear una cuenta.</p>
        </div>
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Volver a usuarios</a>
    </div>

    <section class="ts-module-card" style="max-width: 920px;">
        <form method="POST" action="{{ route('admin.users.store') }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="name" class="form-label fw-semibold">Nombre completo</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                           id="name" name="name" value="{{ old('name') }}" maxlength="255" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label for="email" class="form-label fw-semibold">Correo electrónico</label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror"
                           id="email" name="email" value="{{ old('email') }}" maxlength="255" required>
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label for="role" class="form-label fw-semibold">Rol</label>
                    <select class="form-select @error('role') is-invalid @enderror" id="role" name="role" required>
                        <option value="">Selecciona un rol</option>
                        <option value="administrador" @selected(old('role') === 'administrador')>Administrador</option>
                        <option value="tecnico" @selected(old('role') === 'tecnico')>Técnico</option>
                        <option value="solicitante" @selected(old('role') === 'solicitante')>Solicitante</option>
                    </select>
                    @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label for="password" class="form-label fw-semibold">Contraseña inicial</label>
                    <input type="password" class="form-control @error('password') is-invalid @enderror"
                           id="password" name="password" minlength="12" autocomplete="new-password" required>
                    @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div class="form-text">La contraseña debe tener al menos 12 caracteres.</div>
                </div>
                <div class="col-md-6">
                    <label for="password_confirmation" class="form-label fw-semibold">Confirmar contraseña</label>
                    <input type="password" class="form-control" id="password_confirmation"
                           name="password_confirmation" minlength="12" autocomplete="new-password" required>
                </div>
            </div>
            <hr class="my-4">
            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-primary" type="submit">Registrar usuario</button>
                <a class="btn btn-outline-secondary" href="{{ route('admin.users.index') }}">Cancelar</a>
            </div>
        </form>
    </section>
@endsection
