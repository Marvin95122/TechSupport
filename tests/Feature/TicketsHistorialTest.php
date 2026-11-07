<?php

namespace Tests\Feature;

use App\Models\CambioEstado;
use App\Models\Categoria;
use App\Models\Equipo;
use App\Models\EstadoTicket;
use App\Models\Prioridad;
use App\Models\Ticket;
use App\Models\TicketObservacion;
use App\Models\TicketSolucion;
use App\Models\User;
use Database\Seeders\CatalogosTicketsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketsHistorialTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogosTicketsSeeder::class);
    }

    private function usuario(
        string $rol = 'solicitante',
        bool $activo = true,
        array $atributos = []
    ): User {
        return User::factory()->create(array_merge([
            'role' => $rol,
            'is_active' => $activo,
        ], $atributos));
    }

    private function equipo(string $codigo = 'EQ-HU20-001', bool $activo = true): Equipo
    {
        return Equipo::create([
            'codigo' => $codigo,
            'tipo' => 'Laptop',
            'marca' => 'Dell',
            'modelo' => 'Latitude',
            'serie' => 'SER-'.$codigo,
            'estado' => 'operativo',
            'activo' => $activo,
        ]);
    }

    private function ticket(
        User $solicitante,
        string $sufijo,
        ?Equipo $equipo = null,
        string $estadoNombre = 'Pendiente',
        ?User $tecnico = null
    ): Ticket {
        $ticket = Ticket::create([
            'folio' => 'TS-S6-'.$sufijo,
            'solicitante_id' => $solicitante->id,
            'equipo_id' => $equipo?->id,
            'categoria_id' => Categoria::where('nombre', 'Hardware')->firstOrFail()->id,
            'prioridad_id' => Prioridad::where('nombre', 'Alta')->firstOrFail()->id,
            'estado_id' => EstadoTicket::where('nombre', $estadoNombre)->firstOrFail()->id,
            'asunto' => 'Incidencia '.$sufijo,
            'descripcion' => 'El equipo presenta una falla que requiere atención técnica.',
        ]);

        if ($tecnico !== null) {
            $administrador = $this->usuario('administrador');
            $ticket->forceFill([
                'tecnico_id' => $tecnico->id,
                'asignado_por_id' => $administrador->id,
                'asignado_en' => now(),
            ])->save();
        }

        return $ticket;
    }

    public function test_tecnico_asignado_agrega_observaciones_y_actividades_sin_sobrescribir(): void
    {
        $tecnico = $this->usuario('tecnico');
        $ticket = $this->ticket($this->usuario(), 'OBSERVACIONES', null, 'En proceso', $tecnico);

        $this->actingAs($tecnico)
            ->post(route('tickets.observaciones.store', $ticket), [
                'tipo' => 'observacion',
                'contenido' => 'Se detectó un cable de alimentación flojo.',
            ])
            ->assertRedirect(route('tickets.show', $ticket));

        $this->post(route('tickets.observaciones.store', $ticket), [
            'tipo' => 'actividad',
            'contenido' => 'Se ajustó el cable y se verificó la conexión.',
        ])->assertRedirect(route('tickets.show', $ticket));

        $this->assertDatabaseCount('ticket_observaciones', 2);
        $this->assertDatabaseHas('ticket_observaciones', [
            'ticket_id' => $ticket->id,
            'usuario_id' => $tecnico->id,
            'tipo' => 'observacion',
            'contenido' => 'Se detectó un cable de alimentación flojo.',
        ]);
        $this->assertDatabaseHas('ticket_observaciones', [
            'ticket_id' => $ticket->id,
            'usuario_id' => $tecnico->id,
            'tipo' => 'actividad',
            'contenido' => 'Se ajustó el cable y se verificó la conexión.',
        ]);
    }

    public function test_tecnico_no_asignado_no_puede_agregar_observaciones(): void
    {
        $responsable = $this->usuario('tecnico');
        $otroTecnico = $this->usuario('tecnico');
        $ticket = $this->ticket($this->usuario(), 'OBS-AJENA', null, 'En proceso', $responsable);

        $this->actingAs($otroTecnico)
            ->post(route('tickets.observaciones.store', $ticket), [
                'tipo' => 'observacion',
                'contenido' => 'Intento no autorizado.',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('ticket_observaciones', 0);
    }

    public function test_solicitante_no_puede_registrar_observaciones(): void
    {
        $tecnico = $this->usuario('tecnico');
        $ticket = $this->ticket($this->usuario(), 'OBS-SOLICITANTE', null, 'En proceso', $tecnico);
        $solicitante = $ticket->solicitante;

        $this->actingAs($solicitante)
            ->post(route('tickets.observaciones.store', $ticket), [
                'tipo' => 'observacion',
                'contenido' => 'No autorizado.',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('ticket_observaciones', 0);
    }

    public function test_ticket_cerrado_no_admite_observaciones(): void
    {
        $tecnico = $this->usuario('tecnico');
        $ticket = $this->ticket($this->usuario(), 'OBS-CERRADO', null, 'Cerrado', $tecnico);

        $this->actingAs($tecnico)
            ->from(route('tickets.show', $ticket))
            ->post(route('tickets.observaciones.store', $ticket), [
                'tipo' => 'actividad',
                'contenido' => 'Actividad posterior al cierre.',
            ])
            ->assertSessionHasErrors('contenido');

        $this->assertDatabaseCount('ticket_observaciones', 0);
    }

    public function test_validacion_impide_observacion_vacia_o_tipo_invalido(): void
    {
        $tecnico = $this->usuario('tecnico');
        $ticket = $this->ticket($this->usuario(), 'OBS-VALIDACION', null, 'En proceso', $tecnico);

        $this->actingAs($tecnico)
            ->from(route('tickets.show', $ticket))
            ->post(route('tickets.observaciones.store', $ticket), [
                'tipo' => 'otro',
                'contenido' => '',
            ])
            ->assertSessionHasErrors(['tipo', 'contenido']);

        $this->assertDatabaseCount('ticket_observaciones', 0);
    }

    public function test_tecnico_asignado_registra_diagnostico_acciones_y_solucion(): void
    {
        $tecnico = $this->usuario('tecnico');
        $ticket = $this->ticket($this->usuario(), 'SOLUCION', null, 'En proceso', $tecnico);

        $this->actingAs($tecnico)
            ->post(route('tickets.soluciones.store', $ticket), [
                'diagnostico' => 'El controlador de red tenía un controlador desactualizado.',
                'acciones_realizadas' => 'Se revisó el adaptador y se reinstaló el controlador.',
                'solucion' => 'La conexión de red quedó restablecida y verificada.',
            ])
            ->assertRedirect(route('tickets.show', $ticket));

        $this->assertDatabaseHas('ticket_soluciones', [
            'ticket_id' => $ticket->id,
            'usuario_id' => $tecnico->id,
            'diagnostico' => 'El controlador de red tenía un controlador desactualizado.',
            'acciones_realizadas' => 'Se revisó el adaptador y se reinstaló el controlador.',
            'solucion' => 'La conexión de red quedó restablecida y verificada.',
        ]);
    }

    public function test_los_registros_de_solucion_se_agregan_sin_reemplazar_los_anteriores(): void
    {
        $tecnico = $this->usuario('tecnico');
        $ticket = $this->ticket($this->usuario(), 'SOLUCIONES-MULTIPLES', null, 'En proceso', $tecnico);

        $this->actingAs($tecnico);

        foreach (['Primera revisión', 'Segunda revisión'] as $texto) {
            $this->post(route('tickets.soluciones.store', $ticket), [
                'diagnostico' => $texto,
                'acciones_realizadas' => 'Se realizaron pruebas de funcionamiento.',
                'solucion' => 'Se aplicó el ajuste correspondiente.',
            ])->assertRedirect(route('tickets.show', $ticket));
        }

        $this->assertDatabaseCount('ticket_soluciones', 2);
        $this->assertDatabaseHas('ticket_soluciones', [
            'ticket_id' => $ticket->id,
            'diagnostico' => 'Primera revisión',
        ]);
        $this->assertDatabaseHas('ticket_soluciones', [
            'ticket_id' => $ticket->id,
            'diagnostico' => 'Segunda revisión',
        ]);
    }

    public function test_no_se_registra_solucion_antes_de_iniciar_la_atencion(): void
    {
        $tecnico = $this->usuario('tecnico');
        $ticket = $this->ticket($this->usuario(), 'SOL-ASIGNADO', null, 'Asignado', $tecnico);

        $this->actingAs($tecnico)
            ->from(route('tickets.show', $ticket))
            ->post(route('tickets.soluciones.store', $ticket), [
                'diagnostico' => 'Diagnóstico de prueba.',
                'acciones_realizadas' => 'Acciones de prueba.',
                'solucion' => 'Solución de prueba.',
            ])
            ->assertSessionHasErrors('diagnostico');

        $this->assertDatabaseCount('ticket_soluciones', 0);
    }

    public function test_ticket_cerrado_no_admite_nuevas_soluciones(): void
    {
        $tecnico = $this->usuario('tecnico');
        $ticket = $this->ticket($this->usuario(), 'SOL-CERRADO', null, 'Cerrado', $tecnico);

        $this->actingAs($tecnico)
            ->from(route('tickets.show', $ticket))
            ->post(route('tickets.soluciones.store', $ticket), [
                'diagnostico' => 'Diagnóstico de prueba.',
                'acciones_realizadas' => 'Acciones de prueba.',
                'solucion' => 'Solución de prueba.',
            ])
            ->assertSessionHasErrors('diagnostico');

        $this->assertDatabaseCount('ticket_soluciones', 0);
    }

    public function test_tecnico_no_asignado_no_puede_registrar_solucion(): void
    {
        $responsable = $this->usuario('tecnico');
        $otroTecnico = $this->usuario('tecnico');
        $ticket = $this->ticket($this->usuario(), 'SOL-AJENA', null, 'En proceso', $responsable);

        $this->actingAs($otroTecnico)
            ->post(route('tickets.soluciones.store', $ticket), [
                'diagnostico' => 'Intento no autorizado.',
                'acciones_realizadas' => 'Intento no autorizado.',
                'solucion' => 'Intento no autorizado.',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('ticket_soluciones', 0);
    }

    public function test_historial_del_ticket_muestra_eventos_en_orden_cronologico(): void
    {
        $tecnico = $this->usuario('tecnico');
        $solicitante = $this->usuario('solicitante');
        $ticket = $this->ticket($solicitante, 'CRONOLOGIA', null, 'Resuelto', $tecnico);

        $ticket->forceFill(['creado_en' => now()->subDays(4)])->save();

        $pendiente = EstadoTicket::where('nombre', 'Pendiente')->firstOrFail();
        $asignado = EstadoTicket::where('nombre', 'Asignado')->firstOrFail();
        $enProceso = EstadoTicket::where('nombre', 'En proceso')->firstOrFail();
        $resuelto = EstadoTicket::where('nombre', 'Resuelto')->firstOrFail();

        CambioEstado::create([
            'ticket_id' => $ticket->id,
            'usuario_id' => $this->usuario('administrador')->id,
            'estado_anterior_id' => $pendiente->id,
            'estado_nuevo_id' => $asignado->id,
            'fecha' => now()->subDays(3),
        ]);

        CambioEstado::create([
            'ticket_id' => $ticket->id,
            'usuario_id' => $tecnico->id,
            'estado_anterior_id' => $asignado->id,
            'estado_nuevo_id' => $enProceso->id,
            'fecha' => now()->subDays(2),
        ]);

        TicketObservacion::create([
            'ticket_id' => $ticket->id,
            'usuario_id' => $tecnico->id,
            'tipo' => 'observacion',
            'contenido' => 'Se identificó una conexión intermitente.',
            'fecha' => now()->subDay(),
        ]);

        TicketSolucion::create([
            'ticket_id' => $ticket->id,
            'usuario_id' => $tecnico->id,
            'diagnostico' => 'Conector flojo.',
            'acciones_realizadas' => 'Se ajustó el conector.',
            'solucion' => 'La conexión quedó estable.',
            'fecha' => now(),
        ]);

        $this->actingAs($solicitante)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSeeInOrder([
                'Solicitud registrada',
                'Cambio de estado',
                'Observación registrada',
                'Diagnóstico y solución registrados',
            ])
            ->assertSee('Se identificó una conexión intermitente.')
            ->assertSee('La conexión quedó estable.');
    }

    public function test_usuario_ajeno_no_puede_consultar_historial_del_ticket(): void
    {
        $ticket = $this->ticket($this->usuario(), 'HISTORIAL-PRIVADO');

        $this->actingAs($this->usuario('solicitante'))
            ->get(route('tickets.show', $ticket))
            ->assertForbidden();
    }

    public function test_administrador_consulta_todos_los_tickets_del_equipo_incluso_si_esta_inactivo(): void
    {
        $equipo = $this->equipo('EQ-HU20-INACTIVO', false);
        $tecnico = $this->usuario('tecnico');
        $primero = $this->ticket($this->usuario(), 'EQ-UNO', $equipo, 'En proceso', $tecnico);
        $segundo = $this->ticket($this->usuario(), 'EQ-DOS', $equipo);

        $this->actingAs($this->usuario('administrador'))
            ->get(route('equipos.historial', $equipo))
            ->assertOk()
            ->assertSee('Equipo inactivo')
            ->assertSee($primero->folio)
            ->assertSee($segundo->folio);
    }

    public function test_tecnico_consulta_solo_los_tickets_del_equipo_que_tiene_asignados(): void
    {
        $equipo = $this->equipo('EQ-HU20-TECNICO');
        $tecnico = $this->usuario('tecnico');
        $otroTecnico = $this->usuario('tecnico');

        $propio = $this->ticket($this->usuario(), 'EQ-PROPIO', $equipo, 'En proceso', $tecnico);
        $ajeno = $this->ticket($this->usuario(), 'EQ-AJENO', $equipo, 'En proceso', $otroTecnico);

        $this->actingAs($tecnico)
            ->get(route('equipos.historial', $equipo))
            ->assertOk()
            ->assertSee($propio->folio)
            ->assertDontSee($ajeno->folio);
    }

    public function test_tecnico_sin_asignacion_en_el_equipo_no_puede_consultar_su_historial(): void
    {
        $equipo = $this->equipo('EQ-HU20-SIN-ACCESO');
        $this->ticket($this->usuario(), 'EQ-OTRO-TECNICO', $equipo, 'En proceso', $this->usuario('tecnico'));

        $this->actingAs($this->usuario('tecnico'))
            ->get(route('equipos.historial', $equipo))
            ->assertForbidden();
    }

    public function test_solicitante_no_puede_consultar_historial_del_equipo(): void
    {
        $equipo = $this->equipo('EQ-HU20-SOLICITANTE');

        $this->actingAs($this->usuario('solicitante'))
            ->get(route('equipos.historial', $equipo))
            ->assertForbidden();
    }
}
