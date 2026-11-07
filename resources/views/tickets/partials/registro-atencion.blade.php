@if ($esTecnico && $ticket->estado?->nombre !== 'Cerrado')
    <div class="row g-4 mt-1">
        <div class="col-12 col-xl-6">
            <section class="ts-module-card h-100" aria-label="Registrar observación o actividad">
                <h2 class="h5 fw-bold mb-2">Bitácora de atención</h2>
                <p class="text-secondary small">
                    Registra una observación o una actividad realizada. Cada envío se conserva como un nuevo registro.
                </p>

                <form method="POST" action="{{ route('tickets.observaciones.store', $ticket) }}">
                    @csrf
                    <div class="mb-3">
                        <label for="tipo" class="form-label fw-semibold">Tipo de registro</label>
                        <select id="tipo" name="tipo" class="form-select @error('tipo') is-invalid @enderror" required>
                            <option value="observacion" @selected(old('tipo', 'observacion') === 'observacion')>Observación</option>
                            <option value="actividad" @selected(old('tipo') === 'actividad')>Actividad realizada</option>
                        </select>
                        @error('tipo')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="contenido" class="form-label fw-semibold">Detalle</label>
                        <textarea id="contenido" name="contenido" rows="4" maxlength="5000"
                                  class="form-control @error('contenido') is-invalid @enderror"
                                  placeholder="Describe la observación o actividad realizada..." required>{{ old('contenido') }}</textarea>
                        @error('contenido')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-outline-primary">Agregar a la bitácora</button>
                </form>
            </section>
        </div>

        <div class="col-12 col-xl-6">
            <section class="ts-module-card h-100" aria-label="Registrar diagnóstico y solución">
                <h2 class="h5 fw-bold mb-2">Diagnóstico y solución</h2>

                @if (in_array($ticket->estado?->nombre, ['En proceso', 'Resuelto'], true))
                    <p class="text-secondary small">
                        Documenta el diagnóstico, las acciones ejecutadas y la solución aplicada. Los registros anteriores se conservan.
                    </p>

                    <form method="POST" action="{{ route('tickets.soluciones.store', $ticket) }}">
                        @csrf
                        <div class="mb-3">
                            <label for="diagnostico" class="form-label fw-semibold">Diagnóstico</label>
                            <textarea id="diagnostico" name="diagnostico" rows="3" maxlength="5000"
                                      class="form-control @error('diagnostico') is-invalid @enderror"
                                      placeholder="Describe la causa identificada..." required>{{ old('diagnostico') }}</textarea>
                            @error('diagnostico')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="acciones_realizadas" class="form-label fw-semibold">Acciones ejecutadas</label>
                            <textarea id="acciones_realizadas" name="acciones_realizadas" rows="3" maxlength="5000"
                                      class="form-control @error('acciones_realizadas') is-invalid @enderror"
                                      placeholder="Describe las pruebas y acciones realizadas..." required>{{ old('acciones_realizadas') }}</textarea>
                            @error('acciones_realizadas')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="solucion" class="form-label fw-semibold">Solución aplicada</label>
                            <textarea id="solucion" name="solucion" rows="3" maxlength="5000"
                                      class="form-control @error('solucion') is-invalid @enderror"
                                      placeholder="Describe cómo quedó solucionada la incidencia..." required>{{ old('solucion') }}</textarea>
                            @error('solucion')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary">Guardar diagnóstico y solución</button>
                    </form>
                @else
                    <div class="alert alert-info mb-0" role="status">
                        El registro de diagnóstico y solución estará disponible cuando el ticket se encuentre En proceso.
                    </div>
                @endif
            </section>
        </div>
    </div>
@endif
