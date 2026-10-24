@extends('layouts.app')

@section('title', 'Administración de usuarios')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <p class="text-uppercase text-secondary small fw-semibold mb-1">Administración / Usuarios</p>
            <h1 class="ts-page-heading">Administración de usuarios</h1>
            <p class="text-secondary mb-0">Consulta y administra las cuentas del sistema.</p>
        </div>
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary">Registrar usuario</a>
    </div>

    <section class="ts-module-card" aria-label="Listado de usuarios">
        @if (session('status'))
            <div class="alert alert-success" role="status">{{ session('status') }}</div>
        @endif
        @if ($errors->has('account'))
            <div class="alert alert-danger" role="alert">{{ $errors->first('account') }}</div>
        @endif

        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead>
                    <tr>
                        <th scope="col">ID</th><th scope="col">Nombre</th>
                        <th scope="col">Correo electrónico</th><th scope="col">Rol</th>
                        <th scope="col">Estado</th><th scope="col">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>{{ $user->id }}</td>
                            <td class="fw-semibold">{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>
                                @if ($user->role === 'administrador') Administrador
                                @elseif ($user->role === 'tecnico') Técnico
                                @elseif ($user->role === 'solicitante') Solicitante
                                @else Sin rol definido @endif
                            </td>
                            <td>
                                <span class="badge {{ $user->is_active ? 'text-bg-success' : 'text-bg-danger' }}">
                                    {{ $user->is_active ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-2">
                                    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                                    <form action="{{ route('admin.users.toggle-status', $user) }}"
                                          method="POST"
                                          onsubmit="return confirm('¿Confirmas que deseas {{ $user->is_active ? 'desactivar' : 'activar' }} esta cuenta?');">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                                class="btn btn-sm {{ $user->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}"
                                                @disabled($user->id === auth()->id() && $user->is_active)>
                                            {{ $user->is_active ? 'Desactivar' : 'Activar' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-secondary py-4">No hay usuarios registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <p class="text-secondary small mt-3 mb-0">
            Las cuentas desactivadas se conservan para mantener la trazabilidad, pero no pueden acceder a las funciones protegidas.
        </p>
    </section>
@endsection
