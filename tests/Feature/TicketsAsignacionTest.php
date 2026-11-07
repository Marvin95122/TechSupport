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

class TicketsAsignacionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogosTicketsSeeder::class);
    }

    private function usuario(string $rol = 'solicitante', bool $activo = true, array $atributos = []): User
    {
        return User::factory()->create(array_merge([
            'role' => $rol,
            'is_active' => $activo,
        ], $atributos));
    }

    private function ticket(User $solicitante, string $sufijo): Ticket
    {
        return Ticket::create([
            'folio' => 'TS-HU13-'.$sufijo,
            'solicitante_id' => $solicitante->id,
            'equipo_id' => null,
            'categoria_id' => Categoria::where('nombre', 'Hardware')->firstOrFail()->id,
            'prioridad_id' => Prioridad::where('nombre', 'Alta')->firstOrFail()->id,
            'estado_id' => EstadoTicket::where('nombre', 'Pendiente')->firstOrFail()->id,
            'asunto' => 'Incidencia '.$sufijo,
            'descripcion' => 'El equipo presenta una falla que requiere atención técnica.',
        ]);
    }

    public function test_visitante_no_puede_asignar_un_ticket(): void
    {
        $ticket = $this->ticket($this->usuario(), 'VISITANTE');
        $this->post(route('admin.tickets.assign', $ticket), [
            'tecnico_id' => $this->usuario('tecnico')->id,
        ])->assertRedirect(route('login'));
        $this->assertDatabaseCount('cambios_estado', 0);
    }

    public function test_solicitante_y_tecnico_no_pueden_asignar_tickets(): void
    {
        $ticket = $this->ticket($this->usuario(), 'ROLES');
        $tecnico = $this->usuario('tecnico');

        foreach (['solicitante', 'tecnico'] as $rol) {
            $this->actingAs($this->usuario($rol))
                ->post(route('admin.tickets.assign', $ticket), ['tecnico_id' => $tecnico->id])
                ->assertForbidden();
        }

        $this->assertDatabaseCount('cambios_estado', 0);
    }

    public function test_administrador_asigna_tecnico_activo_y_registra_responsable_fecha_y_estado(): void
    {
        $administrador = $this->usuario('administrador');
        $tecnico = $this->usuario('tecnico', true, ['name' => 'Técnico disponible']);
        $ticket = $this->ticket($this->usuario(), 'CORRECTA');

        $this->actingAs($administrador)
            ->post(route('admin.tickets.assign', $ticket), ['tecnico_id' => $tecnico->id])
            ->assertRedirect(route('tickets.show', $ticket))
            ->assertSessionHas('status');

        $ticketActual = $ticket->fresh();
        $this->assertSame($tecnico->id, $ticketActual->tecnico_id);
        $this->assertSame($administrador->id, $ticketActual->asignado_por_id);
        $this->assertSame('Asignado', $ticketActual->estado->nombre);
        $this->assertNotNull($ticketActual->asignado_en);

        $this->assertDatabaseHas('cambios_estado', [
            'ticket_id' => $ticket->id,
            'usuario_id' => $administrador->id,
            'estado_anterior_id' => EstadoTicket::where('nombre', 'Pendiente')->firstOrFail()->id,
            'estado_nuevo_id' => EstadoTicket::where('nombre', 'Asignado')->firstOrFail()->id,
        ]);
        $this->assertDatabaseCount('cambios_estado', 1);
    }

    public function test_no_permite_asignar_un_tecnico_inactivo(): void
    {
        $administrador = $this->usuario('administrador');
        $tecnicoInactivo = $this->usuario('tecnico', false);
        $ticket = $this->ticket($this->usuario(), 'INACTIVO');

        $this->actingAs($administrador)
            ->from(route('tickets.show', $ticket))
            ->post(route('admin.tickets.assign', $ticket), ['tecnico_id' => $tecnicoInactivo->id])
            ->assertSessionHasErrors('tecnico_id');

        $this->assertNull($ticket->fresh()->tecnico_id);
        $this->assertSame('Pendiente', $ticket->fresh()->estado->nombre);
        $this->assertDatabaseCount('cambios_estado', 0);
    }

    public function test_no_permite_asignar_usuarios_que_no_tienen_rol_tecnico(): void
    {
        $administrador = $this->usuario('administrador');
        $solicitante = $this->usuario('solicitante');
        $ticket = $this->ticket($this->usuario(), 'ROL-INCORRECTO');

        $this->actingAs($administrador)
            ->from(route('tickets.show', $ticket))
            ->post(route('admin.tickets.assign', $ticket), ['tecnico_id' => $solicitante->id])
            ->assertSessionHasErrors('tecnico_id');

        $this->assertNull($ticket->fresh()->tecnico_id);
        $this->assertDatabaseCount('cambios_estado', 0);
    }

    public function test_no_permite_asignar_un_ticket_que_ya_no_esta_pendiente(): void
    {
        $administrador = $this->usuario('administrador');
        $tecnico = $this->usuario('tecnico');
        $ticket = $this->ticket($this->usuario(), 'ESTADO');
        $ticket->forceFill([
            'estado_id' => EstadoTicket::where('nombre', 'Asignado')->firstOrFail()->id,
        ])->save();

        $this->actingAs($administrador)
            ->from(route('tickets.show', $ticket))
            ->post(route('admin.tickets.assign', $ticket), ['tecnico_id' => $tecnico->id])
            ->assertSessionHasErrors('tecnico_id');

        $this->assertNull($ticket->fresh()->tecnico_id);
        $this->assertDatabaseCount('cambios_estado', 0);
    }

    public function test_no_permite_reasignar_un_ticket_que_ya_tiene_tecnico(): void
    {
        $administrador = $this->usuario('administrador');
        $tecnicoActual = $this->usuario('tecnico');
        $otroTecnico = $this->usuario('tecnico');
        $ticket = $this->ticket($this->usuario(), 'REASIGNACION');
        $ticket->forceFill([
            'tecnico_id' => $tecnicoActual->id,
            'asignado_por_id' => $administrador->id,
            'asignado_en' => now(),
            'estado_id' => EstadoTicket::where('nombre', 'Asignado')->firstOrFail()->id,
        ])->save();

        $this->actingAs($administrador)
            ->from(route('tickets.show', $ticket))
            ->post(route('admin.tickets.assign', $ticket), ['tecnico_id' => $otroTecnico->id])
            ->assertSessionHasErrors('tecnico_id');

        $this->assertSame($tecnicoActual->id, $ticket->fresh()->tecnico_id);
        $this->assertDatabaseCount('cambios_estado', 0);
    }

    public function test_administrador_ve_el_selector_busqueda_y_no_carga_el_catalogo_completo(): void
    {
        $administrador = $this->usuario('administrador');
        $ticket = $this->ticket($this->usuario(), 'FORMULARIO');
        $this->usuario('tecnico', true, ['name' => 'Técnico disponible']);
        $this->usuario('tecnico', false, ['name' => 'Técnico inactivo']);

        $this->actingAs($administrador)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Asignación técnica')
            ->assertSee('Buscar técnico responsable')
            ->assertSee('data-ts-technician-picker', false)
            ->assertDontSee('Técnico inactivo');
    }

    public function test_solicitante_no_visualiza_el_formulario_de_asignacion(): void
    {
        $solicitante = $this->usuario('solicitante');
        $ticket = $this->ticket($solicitante, 'VISTA-SOLICITANTE');

        $this->actingAs($solicitante)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertDontSee('Asignación técnica')
            ->assertDontSee('Asignar técnico');
    }
}
