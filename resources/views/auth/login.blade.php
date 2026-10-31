<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#14263e">
    <title>Iniciar sesión | TechSupport</title>
    @vite('resources/js/app.js')
</head>
<body class="ts-login-body">
    @include('partials.icons')
    <div class="ts-login-shell">
        <aside class="ts-login-presentation" aria-label="Presentación de TechSupport">
            <div class="ts-login-logo">
                <span class="ts-brand-mark" aria-hidden="true">TS</span>
                <span>TechSupport</span>
            </div>
            <div>
                <h2>Soporte técnico, organizado en un solo lugar.</h2>
                <p>Una experiencia sencilla para registrar, atender y dar seguimiento a las solicitudes de soporte tecnológico.</p>
            </div>
            <small>TechSupport · BinniBus Oaxaca · Proyecto académico</small>
        </aside>
        <main class="ts-login-main">
            <section class="ts-login-card" aria-labelledby="tsLoginTitle">
                <span class="ts-login-eyebrow">Bienvenido de nuevo</span>
                <h1 id="tsLoginTitle">Iniciar sesión</h1>
                <p class="ts-login-desc">Ingresa tus credenciales para acceder a tu espacio de trabajo.</p>
                <form method="POST" action="{{ route('login.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label for="email" class="form-label">Correo electrónico</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}"
                               class="form-control @error('email') is-invalid @enderror"
                               placeholder="correo@ejemplo.com" autocomplete="username" required autofocus>
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña</label>
                        <input type="password" id="password" name="password"
                               class="form-control @error('password') is-invalid @enderror"
                               autocomplete="current-password" required>
                        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-check mb-4">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember" value="1"
                               @checked(old('remember'))>
                        <label class="form-check-label" for="remember">Recordar mi sesión</label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Iniciar sesión</button>
                </form>
                <p class="ts-login-footnote">Acceso exclusivo para usuarios autorizados del sistema de soporte técnico.</p>
            </section>
        </main>
    </div>
    @if (session('status'))
        <div class="ts-flash-fallback" data-ts-flash data-tone="success" role="status">{{ session('status') }}</div>
    @endif
</body>
</html>
