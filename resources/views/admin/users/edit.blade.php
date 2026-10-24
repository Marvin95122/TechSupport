<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editar usuario - TechSupport</title>
    @vite('resources/js/app.js')
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand fw-bold"
           href="{{ route('admin.users.index') }}">
            TechSupport
        </a>

        <a href="{{ route('admin.users.index') }}"
           class="btn btn-outline-light btn-sm">
            Volver a usuarios
        </a>
    </div>
</nav>

<main class="container py-5">
    <div class="card shadow-sm border-0 mx-auto" style="max-width: 700px;">
        <div class="card-body p-4">
            <h1 class="h3 mb-4">Editar usuario</h1>

            <form action="{{ route('admin.users.update', $user) }}"
                  method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="name" class="form-label">
                        Nombre completo
                    </label>
                    <input type="text"
                           id="name"
                           name="name"
                           class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name', $user->name) }}"
                           maxlength="255"
                           required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">
                        Correo electrónico
                    </label>
                    <input type="email"
                           id="email"
                           name="email"
                           class="form-control @error('email') is-invalid @enderror"
                           value="{{ old('email', $user->email) }}"
                           maxlength="255"
                           required>
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="role" class="form-label">Rol</label>
                    <select id="role"
                            name="role"
                            class="form-select @error('role') is-invalid @enderror"
                            required>
                        <option value="administrador"
                            @selected(old('role', $user->role) === 'administrador')>
                            Administrador
                        </option>
                        <option value="tecnico"
                            @selected(old('role', $user->role) === 'tecnico')>
                            Técnico
                        </option>
                        <option value="solicitante"
                            @selected(old('role', $user->role) === 'solicitante')>
                            Solicitante
                        </option>
                    </select>
                    @error('role')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <hr>

                <p class="text-secondary">
                    Para conservar la contraseña actual, deja estos campos vacíos.
                </p>

                <div class="mb-3">
                    <label for="password" class="form-label">
                        Nueva contraseña
                    </label>
                    <input type="password"
                           id="password"
                           name="password"
                           class="form-control @error('password') is-invalid @enderror"
                           minlength="12"
                           autocomplete="new-password">
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="password_confirmation" class="form-label">
                        Confirmar nueva contraseña
                    </label>
                    <input type="password"
                           id="password_confirmation"
                           name="password_confirmation"
                           class="form-control"
                           minlength="12"
                           autocomplete="new-password">
                </div>

                <button type="submit" class="btn btn-primary">
                    Guardar cambios
                </button>

                <a href="{{ route('admin.users.index') }}"
                   class="btn btn-outline-secondary ms-2">
                    Cancelar
                </a>
            </form>
        </div>
    </div>
</main>

</body>
</html>