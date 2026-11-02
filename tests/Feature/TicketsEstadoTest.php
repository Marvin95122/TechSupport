<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\EstadoTicket;
use App\Models\Prioridad;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\CatalogosTicketsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketsEstadoTest extends TestCase
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

    private function ticket(User $solicitante, string $sufijo, string $estadoNombre = 'Pendiente'): Ticket
    {
        return Ticket::create([
            'folio' => 'TS-HU14-'.$sufijo,
            'solicitante_id' => $solicitante->id,
            'equipo_id' => null,
            'categoria_id' => Categoria::where('nombre', 'Hardware')->firstOrFail()->id,
            'prioridad_id' => Prioridad::where('nombre', 'Alta')->firstOrFail()->id,
            'estado_id' => EstadoTicket::where('nombre', $estadoNombre)->firstOrFail()->id,
            'asunto' => 'Incidencia '.$sufijo,
            'descripcion' => 'El equipo presenta una falla que requiere atención técnica.',
        ]);
    }

    private function ticketAsignado(User $tecnico, string $sufijo, string $estadoNombre = 'Asignado'): Ticket
    {
        $administrador = $this->usuario('administrador');
        $ticket = $this->ticket($this->usuario('solicitante'), $sufijo, $estadoNombre);

        $ticket->forceFill([
            'tecnico_id' => $tecnico->id,
            'asignado_por_id' => $administrador->id,
            'asignado_en' => now(),
        ])->save();

        return $ticket;
    }

    public function test_visitante_no_puede_actualizar_estado(): void
    {
        $tecnico = $this->usuario('tecnico');
        $ticket = $this->ticketAsignado($tecnico, 'VISITANTE');

        $this->post(route('tickets.estado.update', $ticket), [
            'estado_id' => EstadoTicket::where('nombre', 'En proceso')->firstOrFail()->id,
        ])->assertRedirect(route('login'));

        $this->assertDatabaseCount('cambios_estado', 0);
    }

    public function test_administrador_y_solicitante_no_pueden_actualizar_estado(): void
    {
        $tecnico = $this->usuario('tecnico');
        $ticket = $this->ticketAsignado($tecnico, 'ROLES');
        $estadoNuevo = EstadoTicket::where('nombre', 'En proceso')->firstOrFail()->id;

        foreach (['administrador', 'solicitante'] as $rol) {
            $usuario = $this->usuario($rol);

            $this->actingAs($usuario)
                ->post(route('tickets.estado.update', $ticket), [
                    'estado_id' => $estadoNuevo,
                ])
                ->assertForbidden();
        }

        $this->assertSame('Asignado', $ticket->fresh()->estado->nombre);
        $this->assertDatabaseCount('cambios_estado', 0);
    }

    public function test_tecnico_asignado_avanza_a_en_proceso_y_registra_el_historial(): void
    {
        $tecnico = $this->usuario('tecnico');
        $ticket = $this->ticketAsignado($tecnico, 'INICIAR');
        $estadoAnterior = EstadoTicket::where('nombre', 'Asignado')->firstOrFail();
        $estadoNuevo = EstadoTicket::where('nombre', 'En proceso')->firstOrFail();

        $this->actingAs($tecnico)
            ->post(route('tickets.estado.update', $ticket), [
                'estado_id' => $estadoNuevo->id,
            ])
            ->assertRedirect(route('tickets.show', $ticket))
            ->assertSessionHas('status');

        $this->assertSame('En proceso', $ticket->fresh()->estado->nombre);

        $this->assertDatabaseHas('cambios_estado', [
            'ticket_id' => $ticket->id,
            'usuario_id' => $tecnico->id,
            'estado_anterior_id' => $estadoAnterior->id,
            'estado_nuevo_id' => $estadoNuevo->id,
        ]);
    }

    public function test_tecnico_no_puede_actualizar_un_ticket_asignado_a_otro_tecnico(): void
    {
        $responsable = $this->usuario('tecnico');
        $otroTecnico = $this->usuario('tecnico');
        $ticket = $this->ticketAsignado($responsable, 'AJENO');

        $this->actingAs($otroTecnico)
            ->post(route('tickets.estado.update', $ticket), [
                'estado_id' => EstadoTicket::where('nombre', 'En proceso')->firstOrFail()->id,
            ])
            ->assertForbidden();

        $this->assertSame('Asignado', $ticket->fresh()->estado->nombre);
        $this->assertDatabaseCount('cambios_estado', 0);
    }

    public function test_tecnico_no_puede_actualizar_un_ticket_sin_asignacion(): void
    {
        $tecnico = $this->usuario('tecnico');
        $ticket = $this->ticket($this->usuario('solicitante'), 'SIN-ASIGNAR');

        $this->actingAs($tecnico)
            ->post(route('tickets.estado.update', $ticket), [
                'estado_id' => EstadoTicket::where('nombre', 'En proceso')->firstOrFail()->id,
            ])
            ->assertForbidden();

        $this->assertSame('Pendiente', $ticket->fresh()->estado->nombre);
        $this->assertDatabaseCount('cambios_estado', 0);
    }

    public function test_no_permite_saltar_del_estado_asignado_a_resuelto(): void
    {
        $tecnico = $this->usuario('tecnico');
        $ticket = $this->ticketAsignado($tecnico, 'SALTO');

        $this->actingAs($tecnico)
            ->from(route('tickets.show', $ticket))
            ->post(route('tickets.estado.update', $ticket), [
                'estado_id' => EstadoTicket::where('nombre', 'Resuelto')->firstOrFail()->id,
            ])
            ->assertSessionHasErrors('estado_id');

        $this->assertSame('Asignado', $ticket->fresh()->estado->nombre);
        $this->assertDatabaseCount('cambios_estado', 0);
    }

    public function test_no_permite_regresar_de_en_proceso_a_asignado(): void
    {
        $tecnico = $this->usuario('tecnico');
        $ticket = $this->ticketAsignado($tecnico, 'RETROCESO', 'En proceso');

        $this->actingAs($tecnico)
            ->from(route('tickets.show', $ticket))
            ->post(route('tickets.estado.update', $ticket), [
                'estado_id' => EstadoTicket::where('nombre', 'Asignado')->firstOrFail()->id,
            ])
            ->assertSessionHasErrors('estado_id');

        $this->assertSame('En proceso', $ticket->fresh()->estado->nombre);
        $this->assertDatabaseCount('cambios_estado', 0);
    }

    public function test_al_resolver_se_guarda_fecha_y_cambio_de_estado(): void
    {
        $tecnico = $this->usuario('tecnico');
        $ticket = $this->ticketAsignado($tecnico, 'RESUELTO', 'En proceso');
        $estadoAnterior = EstadoTicket::where('nombre', 'En proceso')->firstOrFail();
        $estadoNuevo = EstadoTicket::where('nombre', 'Resuelto')->firstOrFail();

        $this->actingAs($tecnico)
            ->post(route('tickets.estado.update', $ticket), [
                'estado_id' => $estadoNuevo->id,
            ])
            ->assertRedirect(route('tickets.show', $ticket));

        $ticketActual = $ticket->fresh();

        $this->assertSame('Resuelto', $ticketActual->estado->nombre);
        $this->assertNotNull($ticketActual->resuelto_en);

        $this->assertDatabaseHas('cambios_estado', [
            'ticket_id' => $ticket->id,
            'usuario_id' => $tecnico->id,
            'estado_anterior_id' => $estadoAnterior->id,
            'estado_nuevo_id' => $estadoNuevo->id,
        ]);
    }

    public function test_al_cerrar_se_guarda_fecha_y_cambio_de_estado(): void
    {
        $tecnico = $this->usuario('tecnico');
        $ticket = $this->ticketAsignado($tecnico, 'CERRADO', 'Resuelto');
        $estadoAnterior = EstadoTicket::where('nombre', 'Resuelto')->firstOrFail();
        $estadoNuevo = EstadoTicket::where('nombre', 'Cerrado')->firstOrFail();

        $this->actingAs($tecnico)
            ->post(route('tickets.estado.update', $ticket), [
                'estado_id' => $estadoNuevo->id,
            ])
            ->assertRedirect(route('tickets.show', $ticket));

        $ticketActual = $ticket->fresh();

        $this->assertSame('Cerrado', $ticketActual->estado->nombre);
        $this->assertNotNull($ticketActual->cerrado_en);

        $this->assertDatabaseHas('cambios_estado', [
            'ticket_id' => $ticket->id,
            'usuario_id' => $tecnico->id,
            'estado_anterior_id' => $estadoAnterior->id,
            'estado_nuevo_id' => $estadoNuevo->id,
        ]);
    }

    public function test_ticket_cerrado_no_admite_mas_cambios(): void
    {
        $tecnico = $this->usuario('tecnico');
        $ticket = $this->ticketAsignado($tecnico, 'FINAL', 'Cerrado');

        $this->actingAs($tecnico)
            ->from(route('tickets.show', $ticket))
            ->post(route('tickets.estado.update', $ticket), [
                'estado_id' => EstadoTicket::where('nombre', 'Cerrado')->firstOrFail()->id,
            ])
            ->assertSessionHasErrors('estado_id');

        $this->assertSame('Cerrado', $ticket->fresh()->estado->nombre);
        $this->assertDatabaseCount('cambios_estado', 0);
    }

    public function test_tecnico_solo_consulta_el_detalle_de_tickets_asignados_a_su_cuenta(): void
    {
        $tecnico = $this->usuario('tecnico');
        $otroTecnico = $this->usuario('tecnico');
        $ticketPropio = $this->ticketAsignado($tecnico, 'DETALLE-PROPIO');
        $ticketAjeno = $this->ticketAsignado($otroTecnico, 'DETALLE-AJENO');

        $this->actingAs($tecnico)
            ->get(route('tickets.show', $ticketPropio))
            ->assertOk()
            ->assertSee('Seguimiento técnico')
            ->assertSee('Iniciar atención');

        $this->get(route('tickets.show', $ticketAjeno))
            ->assertForbidden();
    }
}
