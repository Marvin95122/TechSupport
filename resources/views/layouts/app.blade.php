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
                        <a href="{{ route('tickets.index') }}" aria-label="Consultas de tickets" title="Consultas"
                           class="ts-nav-link {{ request()->routeIs('tickets.index', 'tickets.show') ? 'is-active' : '' }}"
                           @if(request()->routeIs('tickets.index', 'tickets.show')) aria-current="page" @endif>
                            <svg class="ts-icon"><use href="#ts-icon-chart"/></svg>
                            <span class="ts-nav-text">Consultas</span>
                        </a>
                        <a href="{{ route('admin.reportes.atencion') }}" aria-label="Reportes de atención" title="Reportes"
                           class="ts-nav-link {{ request()->routeIs('admin.reportes.*') ? 'is-active' : '' }}"
                           @if(request()->routeIs('admin.reportes.*')) aria-current="page" @endif>
                            <svg class="ts-icon"><use href="#ts-icon-activity"/></svg>
                            <span class="ts-nav-text">Reportes</span>
                        </a>
                        <a href="{{ route('historial.index') }}" aria-label="Historial de atención" title="Historial"
                           class="ts-nav-link {{ request()->routeIs('historial.index') ? 'is-active' : '' }}"
                           @if(request()->routeIs('historial.index')) aria-current="page" @endif>
                            <svg class="ts-icon"><use href="#ts-icon-history"/></svg>
                            <span class="ts-nav-text">Historial</span>
                        </a>
                    @elseif ($currentRole === 'tecnico')
                        <a href="{{ route('tecnico.asignaciones.index') }}" aria-label="Mis asignaciones" title="Mis asignaciones"
                           class="ts-nav-link {{ request()->routeIs('tecnico.asignaciones.index', 'tickets.show') ? 'is-active' : '' }}"
                           @if(request()->routeIs('tecnico.asignaciones.index', 'tickets.show')) aria-current="page" @endif>
                            <svg class="ts-icon"><use href="#ts-icon-clipboard"/></svg>
                            <span class="ts-nav-text">Mis asignaciones</span>
                        </a>
                        <a href="{{ route('tickets.index') }}" aria-label="Consultar tickets asignados" title="Consultas"
                           class="ts-nav-link {{ request()->routeIs('tickets.index') ? 'is-active' : '' }}"
                           @if(request()->routeIs('tickets.index')) aria-current="page" @endif>
                            <svg class="ts-icon"><use href="#ts-icon-chart"/></svg>
                            <span class="ts-nav-text">Consultas</span>
                        </a>
                        <a href="{{ route('historial.index') }}" aria-label="Historial de atención" title="Historial"
                           class="ts-nav-link {{ request()->routeIs('historial.index') ? 'is-active' : '' }}"
                           @if(request()->routeIs('historial.index')) aria-current="page" @endif>
                            <svg class="ts-icon"><use href="#ts-icon-history"/></svg>
                            <span class="ts-nav-text">Historial</span>
                        </a>
                    @elseif ($currentRole === 'solicitante')
                        <a href="{{ route('tickets.create') }}" aria-label="Nueva solicitud" title="Nueva solicitud"
                           class="ts-nav-link {{ request()->routeIs('tickets.create') ? 'is-active' : '' }}"
                           @if(request()->routeIs('tickets.create')) aria-current="page" @endif>
                            <svg class="ts-icon"><use href="#ts-icon-ticket"/></svg>
                            <span class="ts-nav-text">Nueva solicitud</span>
                        </a>
                        <a href="{{ route('tickets.index') }}" aria-label="Mis tickets" title="Mis tickets"
                           class="ts-nav-link {{ request()->routeIs('tickets.index', 'tickets.show') ? 'is-active' : '' }}"
                           @if(request()->routeIs('tickets.index', 'tickets.show')) aria-current="page" @endif>
                            <svg class="ts-icon"><use href="#ts-icon-clipboard"/></svg>
                            <span class="ts-nav-text">Mis tickets</span>
                        </a>
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
