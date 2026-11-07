<section class="ts-module-card mt-4" aria-label="Historial de atención">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <h2 class="h5 fw-bold mb-1">Historial de atención</h2>
            <p class="text-secondary small mb-0">
                Cronología de registro, cambios de estado, actividades, observaciones y soluciones.
            </p>
        </div>
        <span class="badge text-bg-light">{{ $historial->count() }} eventos</span>
    </div>

    @if ($historial->isEmpty())
        <div class="alert alert-light border mb-0">
            Todavía no hay eventos registrados para esta solicitud.
        </div>
    @else
        <div class="d-flex flex-column">
            @foreach ($historial as $evento)
                <article class="border-start border-2 ps-3 pb-4 ms-1">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <strong>{{ $evento['titulo'] }}</strong>
                            @if ($evento['tipo'] === 'estado')
                                <span class="badge text-bg-info">Estado</span>
                            @elseif ($evento['tipo'] === 'observacion')
                                <span class="badge text-bg-secondary">Observación</span>
                            @elseif ($evento['tipo'] === 'actividad')
                                <span class="badge text-bg-primary">Actividad</span>
                            @elseif ($evento['tipo'] === 'solucion')
                                <span class="badge text-bg-success">Solución</span>
                            @else
                                <span class="badge text-bg-light">Registro</span>
                            @endif
                        </div>
                        <span class="text-secondary small">
                            {{ $evento['fecha']?->format('d/m/Y H:i') ?? 'Fecha no disponible' }}
                        </span>
                    </div>

                    <p class="mb-1 mt-2" style="white-space: pre-wrap; overflow-wrap: anywhere;">{{ $evento['descripcion'] }}</p>
                    <p class="text-secondary small mb-0">Responsable: {{ $evento['autor'] }}</p>
                </article>
            @endforeach
        </div>
    @endif
</section>
