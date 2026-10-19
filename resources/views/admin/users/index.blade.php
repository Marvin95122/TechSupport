<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Administración de usuarios - TechSupport</title>
    @vite('resources/js/app.js')
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-primary">
    <div class="container">
        <span class="navbar-brand fw-bold">TechSupport</span>

        <a href="{{ route('dashboard') }}"
           class="btn btn-outline-light btn-sm">
            Volver al panel
        </a>
    </div>
</nav>

<main class="container py-5">
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">

            <div class="d-flex flex-wrap justify-content-between
                        align-items-center gap-3 mb-3">
                <div>
                    <h1 class="h3 mb-1">Administración de usuarios</h1>
                    <p class="text-secondary mb-0">
                        Consulta y administra las cuentas del sistema.
                    </p>
                </div>

                <a href="{{ route('admin.users.create') }}"
                   class="btn btn-primary">
                    Registrar usuario
                </a>
            </div>

            @if (session('status'))
                <div class="alert alert-success" role="status">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->has('account'))
                <div class="alert alert-danger" role="alert">
                    {{ $errors->first('account') }}
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-primary">
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Correo electrónico</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($users as $user)
                            <tr>
                                <td>{{ $user->id }}</td>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>

                                <td>
                                    @if ($user->role === 'administrador')
                                        Administrador
                                    @elseif ($user->role === 'tecnico')
                                        Técnico
                                    @elseif ($user->role === 'solicitante')
                                        Solicitante
                                    @else
                                        Sin rol definido
                                    @endif
                                </td>

                                <td>
                                    @if ($user->is_active)
                                        <span class="badge text-bg-success">
                                            Activo
                                        </span>
                                    @else
                                        <span class="badge text-bg-danger">
                                            Inactivo
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    <div class="d-flex flex-wrap gap-2">
                                        <a href="{{ route('admin.users.edit', $user) }}"
                                           class="btn btn-sm btn-outline-primary">
                                            Editar
                                        </a>

                                        <form
                                            action="{{ route('admin.users.toggle-status', $user) }}"
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
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    No hay usuarios registrados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <p class="text-secondary small mb-0">
                Las cuentas desactivadas se conservan en el sistema,
                pero no pueden utilizar las funciones protegidas.
            </p>

        </div>
    </div>
</main>

</body>
</html>