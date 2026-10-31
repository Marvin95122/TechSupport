<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#14263e">
    <title>@yield('title', 'Panel principal') | TechSupport</title>
    @vite('resources/js/app.js')
</head>
<body class="ts-body">
    @include('partials.icons')
    @php
        $currentRole = auth()->user()->role;
        $roleLabel = match ($currentRole) {
            'administrador' => 'Administrador',
            'tecnico' => 'Técnico',
            'solicitante' => 'Solicitante',
            default => 'Usuario',
        };
    @endphp
    <div class="ts-shell" id="tsShell">
        <aside class="offcanvas-lg offcanvas-start ts-sidebar" id="tsSidebar" tabindex="-1"
               aria-labelledby="tsSidebarLabel">
            <div class="offcanvas-header ts-mobile-sidebar-header">
                <h2 class="offcanvas-title h5 m-0" id="tsSidebarLabel">TechSupport</h2>
                <button class="btn-close btn-close-white" type="button" data-bs-dismiss="offcanvas"
                        data-bs-target="#tsSidebar" aria-label="Cerrar menú"></button>
            </div>
            <div class="offcanvas-body ts-sidebar-inner">
                <div class="ts-brand">
                    <div class="ts-brand-mark" aria-hidden="true">TS</div>
                    <div class="ts-brand-copy">
                        <span class="ts-brand-name">TechSupport</span>
                        <span class="ts-brand-subtitle">Centro de soporte</span>
                    </div>
                </div>

                <div class="ts-profile">
                    <div class="ts-profile-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</div>
                    <div class="ts-profile-copy overflow-hidden">
                        <strong class="d-block text-truncate">{{ auth()->user()->name }}</strong>
                        <span class="ts-profile-role">{{ $roleLabel }}</span>
                    </div>
                </div>

                <nav class="ts-nav" aria-label="Navegación principal">
                    <span class="ts-nav-heading">ESPACIO DE TRABAJO</span>
                    <a href="{{ route('dashboard') }}" aria-label="Inicio" title="Inicio"
                       class="ts-nav-link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}"
                       @if(request()->routeIs('dashboard')) aria-current="page" @endif>
                        <svg class="ts-icon"><use href="#ts-icon-home"/></svg>
                        <span class="ts-nav-text">Inicio</span>
                    </a>
                    @if ($currentRole === 'administrador')
                        <a href="{{ route('admin.users.index') }}" aria-label="Usuarios" title="Usuarios"
                           class="ts-nav-link {{ request()->routeIs('admin.users.*') ? 'is-active' : '' }}"
                           @if(request()->routeIs('admin.users.*')) aria-current="page" @endif>
                            <svg class="ts-icon"><use href="#ts-icon-users"/></svg>
                            <span class="ts-nav-text">Usuarios</span>
                        </a>
                        <a href="{{ route('admin.equipos.index') }}" aria-label="Equipos" title="Equipos"
                           class="ts-nav-link {{ request()->routeIs('admin.equipos.*') ? 'is-active' : '' }}"
                           @if(request()->routeIs('admin.equipos.*')) aria-current="page" @endif>
                            <svg class="ts-icon"><use href="#ts-icon-monitor"/></svg>
                            <span class="ts-nav-text">Equipos</span>
                        </a>
                        <span class="ts-nav-link ts-nav-upcoming" title="Tickets: Sprint 4">
                            <svg class="ts-icon"><use href="#ts-icon-ticket"/></svg>
                            <span class="ts-nav-text">Tickets</span><small class="ts-nav-meta">Sprint 4</small>
                        </span>
                        <span class="ts-nav-link ts-nav-upcoming" title="Historial: Sprint 6">
                            <svg class="ts-icon"><use href="#ts-icon-history"/></svg>
                            <span class="ts-nav-text">Historial</span><small class="ts-nav-meta">Sprint 6</small>
                        </span>
                        <span class="ts-nav-link ts-nav-upcoming" title="Consultas: Sprint 7">
                            <svg class="ts-icon"><use href="#ts-icon-chart"/></svg>
                            <span class="ts-nav-text">Consultas</span><small class="ts-nav-meta">Sprint 7</small>
                        </span>
                    @elseif ($currentRole === 'tecnico')
                        <span class="ts-nav-link ts-nav-upcoming" title="Mis asignaciones: Sprint 5">
                            <svg class="ts-icon"><use href="#ts-icon-clipboard"/></svg>
                            <span class="ts-nav-text">Mis asignaciones</span><small class="ts-nav-meta">Sprint 5</small>
                        </span>
                        <span class="ts-nav-link ts-nav-upcoming" title="Seguimiento: Sprint 5">
                            <svg class="ts-icon"><use href="#ts-icon-activity"/></svg>
                            <span class="ts-nav-text">Seguimiento</span><small class="ts-nav-meta">Sprint 5</small>
                        </span>
                        <span class="ts-nav-link ts-nav-upcoming" title="Historial: Sprint 6">
                            <svg class="ts-icon"><use href="#ts-icon-history"/></svg>
                            <span class="ts-nav-text">Historial</span><small class="ts-nav-meta">Sprint 6</small>
                        </span>
                    @elseif ($currentRole === 'solicitante')
                        <span class="ts-nav-link ts-nav-upcoming" title="Nueva solicitud: Sprint 4">
                            <svg class="ts-icon"><use href="#ts-icon-ticket"/></svg>
                            <span class="ts-nav-text">Nueva solicitud</span><small class="ts-nav-meta">Sprint 4</small>
                        </span>
                        <span class="ts-nav-link ts-nav-upcoming" title="Mis tickets: Sprint 4">
                            <svg class="ts-icon"><use href="#ts-icon-clipboard"/></svg>
                            <span class="ts-nav-text">Mis tickets</span><small class="ts-nav-meta">Sprint 4</small>
                        </span>
                    @endif
                </nav>

                <div class="ts-sidebar-footer mt-auto">
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="ts-logout" title="Cerrar sesión" aria-label="Cerrar sesión">
                            <svg class="ts-icon"><use href="#ts-icon-log-out"/></svg>
                            <span class="ts-logout-label">Cerrar sesión</span>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <div class="ts-workspace">
            <header class="ts-topbar">
                <button class="ts-icon-button d-lg-none" type="button" data-bs-toggle="offcanvas"
                        data-bs-target="#tsSidebar" aria-controls="tsSidebar" aria-label="Abrir menú">
                    <svg class="ts-icon"><use href="#ts-icon-menu"/></svg>
                </button>
                <button class="ts-icon-button d-none d-lg-inline-flex" type="button"
                        id="tsSidebarToggle" aria-controls="tsSidebar" aria-pressed="false"
                        aria-label="Contraer menú" title="Contraer o expandir menú">
                    <svg class="ts-icon"><use href="#ts-icon-panel"/></svg>
                </button>
                <div class="ts-breadcrumb" aria-label="Ubicación actual">
                    <span class="ts-topbar-label">TechSupport</span>
                    <svg class="ts-icon ts-breadcrumb-chevron"><use href="#ts-icon-chevron-right"/></svg>
                    <strong>@yield('title', 'Panel principal')</strong>
                </div>
                <div class="ts-topbar-user ms-auto">
                    <span class="ts-role-pill">{{ $roleLabel }}</span>
                    <span class="ts-topbar-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                </div>
            </header>
            <main class="ts-content" id="contenido-principal">
                @yield('content')
            </main>
        </div>
    </div>

    {{-- Degradación progresiva: si JS está deshabilitado, el mensaje permanece visible. --}}
    @if (session('status'))
        <div class="ts-flash-fallback" data-ts-flash data-tone="success" role="status">{{ session('status') }}</div>
    @endif
    @if ($errors->has('account'))
        <div class="ts-flash-fallback" data-ts-flash data-tone="danger" role="alert">{{ $errors->first('account') }}</div>
    @endif
    @include('partials.confirm-dialog')
</body>
</html>
