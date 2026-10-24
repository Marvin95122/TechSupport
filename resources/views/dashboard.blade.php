<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Panel - TechSupport</title>
    @vite('resources/js/app.js')
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-primary">
    <div class="container">
        <span class="navbar-brand fw-bold">TechSupport</span>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-outline-light btn-sm">
                Cerrar sesión
            </button>
        </form>
    </div>
</nav>

<main class="container py-5">
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">

            <h1 class="h3 mb-3">Panel principal</h1>

            <p>
                Bienvenido,
                <strong>{{ auth()->user()->name }}</strong>.
            </p>

            <p class="mb-2">
                <strong>Correo:</strong>
                {{ auth()->user()->email }}
            </p>

            <p class="mb-3">
                <strong>Rol:</strong>
                @if (auth()->user()->role === 'administrador')
                    Administrador
                @elseif (auth()->user()->role === 'tecnico')
                    Técnico
                @elseif (auth()->user()->role === 'solicitante')
                    Solicitante
                @else
                    Sin rol definido
                @endif
                @if (auth()->user()->role === 'administrador')
                    <div class="mt-3">
                        <a href="{{ route('admin.users.index') }}"
                        class="btn btn-primary">
                            Administrar usuarios
                        </a>
                    </div>
                @endif
            </p>

            <div class="alert alert-info">
                Tu sesión está autenticada. Las opciones disponibles dependen de tu rol.
            </div>

        </div>
    </div>
</main>

</body>
</html>