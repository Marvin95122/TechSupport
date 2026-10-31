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

class TicketsRegistroTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogosTicketsSeeder::class);
    }

    private function solicitante(): User
    {
        return User::factory()->create(['role' => 'solicitante', 'is_active' => true]);
    }

    private function datos(array $cambios = []): array
    {
        return array_merge([
            'asunto' => 'No funciona la computadora',
            'descripcion' => 'Al encender el equipo aparece una pantalla negra y no inicia.',
            'categoria_id' => Categoria::where('nombre', 'Hardware')->firstOrFail()->id,
            'prioridad_id' => Prioridad::where('nombre', 'Media')->firstOrFail()->id,
            'equipo_id' => '',
        ], $cambios);
    }

    public function test_visitante_no_puede_crear_ticket(): void
    {
        $this->get(route('tickets.create'))->assertRedirect(route('login'));
        $this->post(route('tickets.store'), $this->datos())->assertRedirect(route('login'));
    }

    public function test_tecnico_y_administrador_no_pueden_crear_ticket_en_c1(): void
    {
        foreach (['tecnico', 'administrador'] as $role) {
            $usuario = User::factory()->create(['role' => $role, 'is_active' => true]);
            $this->actingAs($usuario)->get(route('tickets.create'))->assertForbidden();
            $this->actingAs($usuario)->post(route('tickets.store'), $this->datos())->assertForbidden();
        }
    }

    public function test_solicitante_visualiza_formulario_y_catalogos(): void
    {
        $this->actingAs($this->solicitante())
            ->get(route('tickets.create'))
            ->assertOk()->assertSee('Nueva solicitud de soporte')
            ->assertSee('Hardware')->assertSee('Crítica');
    }

    public function test_crear_ticket_sin_equipo_asignado_con_estado_pendiente_y_folio_unico(): void
    {
        $usuario = $this->solicitante();
        $this->actingAs($usuario)->post(route('tickets.store'), $this->datos())
            ->assertRedirect(route('tickets.create'))->assertSessionHas('status');

        $ticket = Ticket::firstOrFail();
        $this->assertMatchesRegularExpression('/^TS-[0-9A-HJKMNP-TV-Z]{26}$/', $ticket->folio);
        $this->assertSame($usuario->id, $ticket->solicitante_id);
        $this->assertNull($ticket->equipo_id);
        $this->assertSame('Pendiente', $ticket->estado->nombre);
        $this->assertNotNull($ticket->creado_en);
        $this->assertNull($ticket->tecnico_id);

        $this->post(route('tickets.store'), $this->datos())->assertRedirect();
        $this->assertSame(2, Ticket::count());
        $this->assertCount(2, Ticket::pluck('folio')->unique());
    }

    public function test_asociar_equipo_activo_es_opcional(): void
    {
        $equipo = Equipo::create([
            'codigo' => 'EQ-S4-C1-001', 'tipo' => 'Computadora', 'marca' => 'Dell',
            'modelo' => 'OptiPlex', 'estado' => 'operativo', 'activo' => true,
        ]);
        $this->actingAs($this->solicitante())
            ->post(route('tickets.store'), $this->datos(['equipo_id' => $equipo->id]))
            ->assertRedirect();
        $this->assertSame($equipo->id, Ticket::firstOrFail()->equipo_id);
    }

    public function test_no_acepta_equipo_inactivo(): void
    {
        $equipo = Equipo::create([
            'codigo' => 'EQ-S4-C1-002', 'tipo' => 'Radio', 'marca' => 'Motorola',
            'modelo' => 'Demo', 'estado' => 'fuera_de_servicio', 'activo' => false,
        ]);
        $this->actingAs($this->solicitante())
            ->post(route('tickets.store'), $this->datos(['equipo_id' => $equipo->id]))
            ->assertSessionHasErrors('equipo_id');
        $this->assertDatabaseCount('tickets', 0);
    }

    public function test_no_acepta_categoria_inactiva_ni_prioridad_inexistente(): void
    {
        $categoria = Categoria::where('nombre', 'Hardware')->firstOrFail();
        $categoria->update(['activo' => false]);
        $this->actingAs($this->solicitante())
            ->post(route('tickets.store'), $this->datos(['categoria_id' => $categoria->id]))
            ->assertSessionHasErrors('categoria_id');
        $this->post(route('tickets.store'), $this->datos([
            'categoria_id' => Categoria::where('nombre', 'Software')->firstOrFail()->id,
            'prioridad_id' => 999999,
        ]))->assertSessionHasErrors('prioridad_id');
        $this->assertDatabaseCount('tickets', 0);
    }

    public function test_valida_asunto_y_descripcion(): void
    {
        $this->actingAs($this->solicitante())
            ->post(route('tickets.store'), $this->datos(['asunto' => 'x', 'descripcion' => 'corto']))
            ->assertSessionHasErrors(['asunto', 'descripcion']);
        $this->assertDatabaseCount('tickets', 0);
    }

    public function test_no_permite_suplantar_solicitante_ni_estado(): void
    {
        $real = $this->solicitante();
        $ajeno = $this->solicitante();
        $resuelto = EstadoTicket::where('nombre', 'Resuelto')->firstOrFail();
        $this->actingAs($real)->post(route('tickets.store'), $this->datos([
            'solicitante_id' => $ajeno->id, 'estado_id' => $resuelto->id,
            'folio' => 'FOLIO-FALSIFICADO', 'tecnico_id' => $ajeno->id,
        ]))->assertRedirect();

        $ticket = Ticket::firstOrFail();
        $this->assertSame($real->id, $ticket->solicitante_id);
        $this->assertSame('Pendiente', $ticket->estado->nombre);
        $this->assertNotSame('FOLIO-FALSIFICADO', $ticket->folio);
        $this->assertNull($ticket->tecnico_id);
    }
}
