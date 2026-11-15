<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Equipo;
use App\Models\EstadoTicket;
use App\Models\Prioridad;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\CatalogosTicketsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sprint 9 - CT-04.
 *
 * Pruebas de integración horizontal de los módulos construidos
 * desde el Sprint 2 hasta el Sprint 8.
 *
 * Estas pruebas usan SQLite en memoria por la configuración de phpunit.xml.
 * No deben modificar la base de datos MySQL de desarrollo.
 */
class IntegracionSprint9Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogosTicketsSeeder::class);
    }

    private function usuario(string $rol, bool $activo = true): User
    {
        return User::factory()->create([
            'role' => $rol,
            'is_active' => $activo,
        ]);
    }

    private function estado(string $nombre): EstadoTicket
    {
        return EstadoTicket::query()->where('nombre', $nombre)->firstOrFail();
    }

    private function datosTicket(?Equipo $equipo = null): array
    {
        return [
            'asunto' => 'El equipo no se conecta a la red',
            'descripcion' => 'La computadora no puede acceder a los recursos de red del área.',
            'categoria_id' => Categoria::query()->where('nombre', 'Red')->firstOrFail()->id,
            'prioridad_id' => Prioridad::query()->where('nombre', 'Alta')->firstOrFail()->id,
            'equipo_id' => $equipo?->id,
        ];
    }

    public function test_flujo_integral_desde_registro_de_usuarios_hasta_cierre_y_reporte(): void
    {
        $administrador = $this->usuario('administrador');
        $password = 'ClaveTemporalDePrueba2026!';

        // Paso 1: el administrador registra un solicitante y un técnico.
        foreach ([
            ['name' => 'Solicitante Sprint 9', 'email' => 'solicitante-s9@example.test', 'role' => 'solicitante'],
            ['name' => 'Tecnico Sprint 9', 'email' => 'tecnico-s9@example.test', 'role' => 'tecnico'],
        ] as $datos) {
            $this->actingAs($administrador)
                ->post(route('admin.users.store'), [
                    ...$datos,
                    'password' => $password,
                    'password_confirmation' => $password,
                ])
                ->assertRedirect(route('admin.users.index'));
        }

        $solicitante = User::query()->where('email', 'solicitante-s9@example.test')->firstOrFail();
        $tecnico = User::query()->where('email', 'tecnico-s9@example.test')->firstOrFail();

        $this->assertNotSame($password, $solicitante->password);

        // Paso 2: vincular el ticket con un equipo registrado.
        $equipo = Equipo::create([
            'codigo' => 'EQ-SPRINT09-001',
            'tipo' => 'Computadora',
            'marca' => 'Dell',
            'modelo' => 'OptiPlex',
            'estado' => 'operativo',
            'activo' => true,
        ]);

        // Paso 3: comprobar autenticación real del solicitante.
        $this->actingAs($administrador)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->post(route('login.store'), [
            'email' => $solicitante->email,
            'password' => $password,
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($solicitante);

        // Paso 4: crear el ticket con equipo y verificar folio y estado inicial.
        $this->post(route('tickets.store'), $this->datosTicket($equipo))
            ->assertRedirect(route('tickets.create'))
            ->assertSessionHas('status');

        $ticket = Ticket::query()->firstOrFail();

        $this->assertMatchesRegularExpression('/^TS-[0-9A-HJKMNP-TV-Z]{26}$/', $ticket->folio);
        $this->assertSame($solicitante->id, $ticket->solicitante_id);
        $this->assertSame($equipo->id, $ticket->equipo_id);
        $this->assertSame('Pendiente', $ticket->estado->nombre);
        $this->assertNull($ticket->tecnico_id);

        // Paso 5: asignación exclusiva del administrador.
        $this->actingAs($administrador)
            ->post(route('admin.tickets.assign', $ticket), [
                'tecnico_id' => $tecnico->id,
            ])
            ->assertRedirect(route('tickets.show', $ticket));

        $ticket = $ticket->fresh();
        $this->assertSame($tecnico->id, $ticket->tecnico_id);
        $this->assertSame($administrador->id, $ticket->asignado_por_id);
        $this->assertNotNull($ticket->asignado_en);
        $this->assertSame('Asignado', $ticket->estado->nombre);

        // Paso 6: el técnico ve su asignación y comienza el trabajo.
        $this->actingAs($tecnico)
            ->get(route('tecnico.asignaciones.index'))
            ->assertOk()
            ->assertSee($ticket->folio);

        $this->actingAs($tecnico)
            ->post(route('tickets.estado.update', $ticket), [
                'estado_id' => $this->estado('En proceso')->id,
            ])
            ->assertRedirect(route('tickets.show', $ticket));

        // Paso 7: registrar dos eventos independientes y la solución.
        $this->actingAs($tecnico)
            ->post(route('tickets.observaciones.store', $ticket), [
                'tipo' => 'observacion',
                'contenido' => 'Se confirma que no existe acceso a la red.',
            ])
            ->assertRedirect(route('tickets.show', $ticket));

        $this->actingAs($tecnico)
            ->post(route('tickets.observaciones.store', $ticket), [
                'tipo' => 'actividad',
                'contenido' => 'Se revisó el cableado y la configuración de red.',
            ])
            ->assertRedirect(route('tickets.show', $ticket));

        $this->actingAs($tecnico)
            ->post(route('tickets.soluciones.store', $ticket), [
                'diagnostico' => 'Configuración de red incorrecta.',
                'acciones_realizadas' => 'Se verificó el adaptador y se corrigió la configuración.',
                'solucion' => 'Se restauró la conectividad correctamente.',
            ])
            ->assertRedirect(route('tickets.show', $ticket));

        // Paso 8: resolver y cerrar respetando las transiciones autorizadas.
        foreach (['Resuelto', 'Cerrado'] as $estado) {
            $this->actingAs($tecnico)
                ->post(route('tickets.estado.update', $ticket), [
                    'estado_id' => $this->estado($estado)->id,
                ])
                ->assertRedirect(route('tickets.show', $ticket));
        }

        $ticket = $ticket->fresh();

        $this->assertSame('Cerrado', $ticket->estado->nombre);
        $this->assertNotNull($ticket->resuelto_en);
        $this->assertNotNull($ticket->cerrado_en);
        $this->assertDatabaseCount('cambios_estado', 4);
        $this->assertDatabaseCount('ticket_observaciones', 2);
        $this->assertDatabaseCount('ticket_soluciones', 1);

        $this->assertDatabaseHas('ticket_soluciones', [
            'ticket_id' => $ticket->id,
            'usuario_id' => $tecnico->id,
        ]);

        // Paso 9: verificar historial, equipo y reporte administrativo.
        $this->actingAs($solicitante)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee($ticket->folio);

        $this->actingAs($administrador)
            ->get(route('equipos.historial', $equipo))
            ->assertOk()
            ->assertSee($ticket->folio);

        $this->actingAs($administrador)
            ->get(route('admin.reportes.atencion.detalle', $ticket))
            ->assertOk()
            ->assertSee($ticket->folio)
            ->assertSee('Se restauró la conectividad correctamente.');

        $this->actingAs($administrador)
            ->get(route('admin.reportes.atencion', [
                'estado_id' => $this->estado('Cerrado')->id,
            ]))
            ->assertOk()
            ->assertSee($ticket->folio);
    }

    public function test_el_flujo_integrado_restringe_accesos_y_evitar_modificaciones_ajenas(): void
    {
        $administrador = $this->usuario('administrador');
        $solicitante = $this->usuario('solicitante');
        $otroSolicitante = $this->usuario('solicitante');
        $tecnico = $this->usuario('tecnico');
        $otroTecnico = $this->usuario('tecnico');

        $this->actingAs($solicitante)
            ->post(route('tickets.store'), $this->datosTicket())
            ->assertRedirect(route('tickets.create'));

        $ticket = Ticket::query()->firstOrFail();

        // Nadie ajeno puede consultar ni reasignar la solicitud.
        $this->actingAs($otroSolicitante)
            ->get(route('tickets.show', $ticket))
            ->assertForbidden();

        $this->actingAs($solicitante)
            ->post(route('admin.tickets.assign', $ticket), [
                'tecnico_id' => $tecnico->id,
            ])
            ->assertForbidden();

        $this->actingAs($administrador)
            ->post(route('admin.tickets.assign', $ticket), [
                'tecnico_id' => $tecnico->id,
            ])
            ->assertRedirect(route('tickets.show', $ticket));

        $this->actingAs($otroTecnico)
            ->get(route('tickets.show', $ticket))
            ->assertForbidden();

        $this->actingAs($otroTecnico)
            ->post(route('tickets.estado.update', $ticket), [
                'estado_id' => $this->estado('En proceso')->id,
            ])
            ->assertForbidden();

        $this->actingAs($tecnico)
            ->get(route('admin.reportes.atencion'))
            ->assertForbidden();

        $this->actingAs($solicitante)
            ->get(route('admin.reportes.atencion'))
            ->assertForbidden();

        $this->assertSame('Asignado', $ticket->fresh()->estado->nombre);
        $this->assertSame($tecnico->id, $ticket->fresh()->tecnico_id);
        $this->assertDatabaseCount('cambios_estado', 1);
    }

    public function test_transiciones_invalidas_y_ticket_cerrado_no_alteran_la_persistencia(): void
    {
        $administrador = $this->usuario('administrador');
        $solicitante = $this->usuario('solicitante');
        $tecnico = $this->usuario('tecnico');

        $this->actingAs($solicitante)
            ->post(route('tickets.store'), $this->datosTicket())
            ->assertRedirect(route('tickets.create'));

        $ticket = Ticket::query()->firstOrFail();

        $this->actingAs($administrador)
            ->post(route('admin.tickets.assign', $ticket), [
                'tecnico_id' => $tecnico->id,
            ])
            ->assertRedirect(route('tickets.show', $ticket));

        // No se puede saltar desde Asignado directamente a Cerrado.
        $this->actingAs($tecnico)
            ->from(route('tickets.show', $ticket))
            ->post(route('tickets.estado.update', $ticket), [
                'estado_id' => $this->estado('Cerrado')->id,
            ])
            ->assertSessionHasErrors('estado_id');

        $this->assertSame('Asignado', $ticket->fresh()->estado->nombre);
        $this->assertDatabaseCount('cambios_estado', 1);

        // La solución no puede registrarse antes de iniciar atención.
        $this->actingAs($tecnico)
            ->from(route('tickets.show', $ticket))
            ->post(route('tickets.soluciones.store', $ticket), [
                'diagnostico' => 'Diagnóstico de prueba.',
                'acciones_realizadas' => 'Acciones de prueba.',
                'solucion' => 'Solución de prueba.',
            ])
            ->assertSessionHasErrors('diagnostico');

        $this->assertDatabaseCount('ticket_soluciones', 0);

        // Realizar correctamente las tres transiciones restantes.
        foreach (['En proceso', 'Resuelto', 'Cerrado'] as $estado) {
            $this->actingAs($tecnico)
                ->post(route('tickets.estado.update', $ticket), [
                    'estado_id' => $this->estado($estado)->id,
                ])
                ->assertRedirect(route('tickets.show', $ticket));
        }

        // El ticket cerrado es inmutable respecto de la bitácora y estados.
        $this->actingAs($tecnico)
            ->from(route('tickets.show', $ticket))
            ->post(route('tickets.observaciones.store', $ticket), [
                'tipo' => 'actividad',
                'contenido' => 'Intento posterior al cierre.',
            ])
            ->assertSessionHasErrors('contenido');

        $this->assertSame('Cerrado', $ticket->fresh()->estado->nombre);
        $this->assertDatabaseCount('cambios_estado', 4);
        $this->assertDatabaseCount('ticket_observaciones', 0);
        $this->assertDatabaseCount('ticket_soluciones', 0);
    }
}
