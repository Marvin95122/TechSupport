<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión - TechSupport</title>
    @vite('resources/js/app.js')
</head>
<body class="bg-light">

<div class="container min-vh-100 d-flex align-items-center justify-content-center py-4">
    <div class="card shadow-sm border-0" style="max-width: 460px; width: 100%;">
        <div class="card-body p-4 p-md-5">

            <h1 class="h3 text-primary fw-bold">TechSupport</h1>
            <p class="text-secondary mb-4">
                Ingresa con tu cuenta para acceder al sistema de soporte técnico.
            </p>

            @if (session('status'))
                <div class="alert alert-success" role="status">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.store') }}">
                @csrf

                <div class="mb-3">
                    <label for="email" class="form-label">
                        Correo electrónico
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        class="form-control @error('email') is-invalid @enderror"
                        autocomplete="username"
                        required
                        autofocus
                    >

                    @error('email')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">
                        Contraseña
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control @error('password') is-invalid @enderror"
                        autocomplete="current-password"
                        required
                    >

                    @error('password')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="form-check mb-4">
                    <input
                        type="checkbox"
                        id="remember"
                        name="remember"
                        value="1"
                        class="form-check-input"
                    >

                    <label for="remember" class="form-check-label">
                        Recordar mi sesión
                    </label>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    Iniciar sesión
                </button>
            </form>

            <p class="text-secondary small text-center mt-4 mb-0">
                Sistema de gestión y seguimiento de solicitudes de soporte técnico.
            </p>

        </div>
    </div>
</div>

</body>
</html>