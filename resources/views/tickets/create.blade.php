@extends('layouts.app')
@section('title', 'Nueva solicitud')
@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <p class="text-uppercase text-secondary small fw-semibold mb-1">Soporte técnico / Solicitudes</p>
            <h1 class="ts-page-heading">Nueva solicitud de soporte</h1>
            <p class="text-secondary mb-0">Describe la incidencia para que el área técnica pueda recibirla y darle seguimiento.</p>
        </div>
        <span class="badge text-bg-light border align-self-start px-3 py-2">Sprint 4 · Registro</span>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">Hay datos que debemos corregir antes de registrar la solicitud.</div>
    @endif

    <div class="row g-4">
        <div class="col-12 col-xl-8">
            <section class="ts-module-card" aria-label="Formulario de nueva solicitud">
                @if ($categorias->isEmpty() || $prioridades->isEmpty())
                    <div class="alert alert-warning" role="alert">
                        Todavía no están disponibles las categorías o prioridades. Un administrador debe ejecutar el catálogo inicial.
                    </div>
                @endif

                <form action="{{ route('tickets.store') }}" method="POST">
                    @csrf
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="asunto" class="form-label fw-semibold">Asunto de la solicitud *</label>
                            <input type="text" id="asunto" name="asunto" required minlength="5" maxlength="180"
                                   value="{{ old('asunto') }}" autocomplete="off"
                                   placeholder="Ej. El equipo de oficina no enciende"
                                   class="form-control @error('asunto') is-invalid @enderror">
                            @error('asunto') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label for="descripcion" class="form-label fw-semibold">Descripción de la incidencia *</label>
                            <textarea id="descripcion" name="descripcion" rows="5" required minlength="10" maxlength="5000"
                                      placeholder="Explica qué ocurre, desde cuándo y qué actividad se ve afectada."
                                      class="form-control @error('descripcion') is-invalid @enderror">{{ old('descripcion') }}</textarea>
                            @error('descripcion') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text">No incluyas contraseñas ni información confidencial en la descripción.</div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="categoria_id" class="form-label fw-semibold">Categoría *</label>
                            <select id="categoria_id" name="categoria_id" required
                                    class="form-select @error('categoria_id') is-invalid @enderror">
                                <option value="">Selecciona una categoría</option>
                                @foreach ($categorias as $categoria)
                                    <option value="{{ $categoria->id }}" @selected((string) old('categoria_id') === (string) $categoria->id)>{{ $categoria->nombre }}</option>
                                @endforeach
                            </select>
                            @error('categoria_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="prioridad_id" class="form-label fw-semibold">Prioridad solicitada *</label>
                            <select id="prioridad_id" name="prioridad_id" required
                                    class="form-select @error('prioridad_id') is-invalid @enderror">
                                <option value="">Selecciona una prioridad</option>
                                @foreach ($prioridades as $prioridad)
                                    <option value="{{ $prioridad->id }}" @selected((string) old('prioridad_id') === (string) $prioridad->id)>{{ $prioridad->nombre }}</option>
                                @endforeach
                            </select>
                            @error('prioridad_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text">Se registra la prioridad indicada para su evaluación posterior.</div>
                        </div>
                        <div class="col-12">
                            <label for="equipo_id" class="form-label fw-semibold">Equipo relacionado (opcional)</label>
                            <select id="equipo_id" name="equipo_id" class="form-select @error('equipo_id') is-invalid @enderror">
                                <option value="">No corresponde a un equipo registrado</option>
                                @foreach ($equipos as $equipo)
                                    <option value="{{ $equipo->id }}" @selected((string) old('equipo_id') === (string) $equipo->id)>
                                        {{ $equipo->codigo }} — {{ $equipo->tipo }} · {{ $equipo->marca }} {{ $equipo->modelo }}
                                    </option>
                                @endforeach
                            </select>
                            @error('equipo_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text">Únicamente se ofrecen equipos activos. Puedes registrar una incidencia sin asociar un equipo.</div>
                        </div>
                    </div>
                    <hr class="my-4">
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <button type="submit" class="btn btn-primary" @disabled($categorias->isEmpty() || $prioridades->isEmpty())>
                            Registrar solicitud
                        </button>
                        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </section>
        </div>
        <div class="col-12 col-xl-4">
            <section class="ts-module-card" aria-label="Información sobre la solicitud">
                <h2 class="h5 fw-bold mb-3">¿Qué ocurre después?</h2>
                <div class="d-flex gap-3 align-items-start mb-3">
                    <span class="badge text-bg-primary">1</span>
                    <div><strong class="d-block">Registro</strong><p class="small text-secondary mb-0">Se crea un folio único con tus datos y la fecha.</p></div>
                </div>
                <div class="d-flex gap-3 align-items-start mb-3">
                    <span class="badge text-bg-secondary">2</span>
                    <div><strong class="d-block">Estado Pendiente</strong><p class="small text-secondary mb-0">El ticket queda a la espera de atención y clasificación del área técnica.</p></div>
                </div>
                <div class="d-flex gap-3 align-items-start">
                    <span class="badge text-bg-secondary">3</span>
                    <div><strong class="d-block">Seguimiento</strong><p class="small text-secondary mb-0">Puedes consultar tus tickets y sus estados en la opción Mis tickets.</p></div>
                </div>
            </section>
        </div>
    </div>
@endsection
