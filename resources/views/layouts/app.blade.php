<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Panel principal') | TechSupport</title>
    @vite('resources/js/app.js')
</head>
<body class="ts-body">
    @php
        $currentRole = auth()->user()->role;
        $roleLabel = match ($currentRole) {
            'administrador' => 'Administrador',
            'tecnico' => 'Técnico',
            'solicitante' => 'Solicitante',
            default => 'Usuario',
        };
    @endphp

    <div class="ts-shell">
        <aside class="offcanvas-lg offcanvas-start ts-sidebar" tabindex="-1"
               id="tsSidebar" aria-labelledby="tsSidebarLabel">
            <div class="offcanvas-header text-white border-bottom border-secondary">
                <h2 class="h5 offcanvas-title" id="tsSidebarLabel">TechSupport</h2>
                <button type="button" class="btn-close btn-close-white"
                        data-bs-dismiss="offcanvas" data-bs-target="#tsSidebar"
                        aria-label="Cerrar menú"></button>
            </div>
            <div class="offcanvas-body ts-sidebar-inner">
                <div class="ts-brand d-none d-lg-block">
                    <span class="ts-brand-name">TechSupport</span>
                    <span class="ts-brand-subtitle">Gestión de soporte técnico</span>
                </div>
                <div class="ts-profile">
                    <div class="ts-profile-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</div>
                    <div class="overflow-hidden">
                        <strong class="d-block text-truncate">{{ auth()->user()->name }}</strong>
                        <span class="small text-white-50">{{ $roleLabel }}</span>
                    </div>
                </div>

                <nav class="ts-nav" aria-label="Navegación principal">
                    <span class="ts-nav-heading">MENÚ PRINCIPAL</span>
                    <a href="{{ route('dashboard') }}"
                       class="ts-nav-link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}"
                       @if(request()->routeIs('dashboard')) aria-current="page" @endif>
                        <span>Inicio</span>
                    </a>

                    @if ($currentRole === 'administrador')
                        <a href="{{ route('admin.users.index') }}"
                           class="ts-nav-link {{ request()->routeIs('admin.users.*') ? 'is-active' : '' }}"
                           @if(request()->routeIs('admin.users.*')) aria-current="page" @endif>
                            <span>Usuarios</span>
                        </a>
                        <a href="{{ route('admin.equipos.index') }}"
                           class="ts-nav-link {{ request()->routeIs('admin.equipos.*') ? 'is-active' : '' }}"
                           @if(request()->routeIs('admin.equipos.*')) aria-current="page" @endif>
                            <span>Equipos</span>
                        </a>
                        <span class="ts-nav-link ts-nav-upcoming"><span>Tickets</span><small>Sprint 4</small></span>
                        <span class="ts-nav-link ts-nav-upcoming"><span>Historial</span><small>Sprint 6</small></span>
                        <span class="ts-nav-link ts-nav-upcoming"><span>Consultas</span><small>Sprint 7</small></span>
                    @elseif ($currentRole === 'tecnico')
                        <span class="ts-nav-link ts-nav-upcoming"><span>Mis asignaciones</span><small>Sprint 5</small></span>
                        <span class="ts-nav-link ts-nav-upcoming"><span>Seguimiento</span><small>Sprint 5</small></span>
                        <span class="ts-nav-link ts-nav-upcoming"><span>Historial</span><small>Sprint 6</small></span>
                    @elseif ($currentRole === 'solicitante')
                        <span class="ts-nav-link ts-nav-upcoming"><span>Nueva solicitud</span><small>Sprint 4</small></span>
                        <span class="ts-nav-link ts-nav-upcoming"><span>Mis tickets</span><small>Sprint 4</small></span>
                    @endif
                </nav>

                <form method="POST" action="{{ route('logout') }}" class="mt-auto pt-4">
                    @csrf
                    <button type="submit" class="ts-logout w-100">Cerrar sesión</button>
                </form>
            </div>
        </aside>

        <div class="ts-workspace">
            <header class="ts-topbar">
                <button class="btn btn-outline-secondary d-lg-none" type="button"
                        data-bs-toggle="offcanvas" data-bs-target="#tsSidebar"
                        aria-controls="tsSidebar" aria-label="Abrir menú">
                    ☰
                </button>
                <div>
                    <span class="ts-topbar-label">TechSupport</span>
                    <span class="ts-topbar-separator">/</span>
                    <strong>@yield('title', 'Panel principal')</strong>
                </div>
                <span class="badge rounded-pill text-bg-light border d-none d-sm-inline-block">{{ $roleLabel }}</span>
            </header>

            <main class="ts-content">
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
