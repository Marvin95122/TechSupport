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

class TicketsMisAsignacionesTest extends TestCase
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

    private function ticket(
        User $solicitante,
        string $sufijo,
        string $estadoNombre = 'Pendiente',
        ?User $tecnico = null
    ): Ticket {
        $ticket = Ticket::create([
            'folio' => 'TS-HU15-'.$sufijo,
            'solicitante_id' => $solicitante->id,
            'equipo_id' => null,
            'categoria_id' => Categoria::where('nombre', 'Hardware')->firstOrFail()->id,
            'prioridad_id' => Prioridad::where('nombre', 'Alta')->firstOrFail()->id,
            'estado_id' => EstadoTicket::where('nombre', $estadoNombre)->firstOrFail()->id,
            'asunto' => 'Incidencia '.$sufijo,
            'descripcion' => 'El equipo presenta una falla que requiere atención técnica.',
        ]);

        if ($tecnico !== null) {
            $ticket->forceFill([
                'tecnico_id' => $tecnico->id,
                'asignado_por_id' => $this->usuario('administrador')->id,
                'asignado_en' => now(),
            ])->save();
        }

        return $ticket;
    }

    public function test_visitante_no_puede_consultar_mis_asignaciones(): void
    {
        $this->get(route('tecnico.asignaciones.index'))
            ->assertRedirect(route('login'));
    }

    public function test_administrador_y_solicitante_no_pueden_consultar_mis_asignaciones(): void
    {
        foreach (['administrador', 'solicitante'] as $rol) {
            $this->actingAs($this->usuario($rol))
                ->get(route('tecnico.asignaciones.index'))
                ->assertForbidden();
        }
    }

    public function test_tecnico_solo_ve_sus_tickets_y_los_datos_requeridos(): void
    {
        $tecnico = $this->usuario('tecnico');
        $otroTecnico = $this->usuario('tecnico');

        $ticketPropio = $this->ticket(
            $this->usuario('solicitante'),
            'PROPIO',
            'Asignado',
            $tecnico
        );

        $ticketAjeno = $this->ticket(
            $this->usuario('solicitante'),
            'AJENO',
            'En proceso',
            $otroTecnico
        );

        $ticketSinAsignar = $this->ticket(
            $this->usuario('solicitante'),
            'SIN-ASIGNAR'
        );

        $this->actingAs($tecnico)
            ->get(route('tecnico.asignaciones.index'))
            ->assertOk()
            ->assertSee($ticketPropio->folio)
            ->assertSee('Alta')
            ->assertSee('Asignado')
            ->assertSee('Ver detalle')
            ->assertSee(route('tickets.show', $ticketPropio))
            ->assertDontSee($ticketAjeno->folio)
            ->assertDontSee($ticketSinAsignar->folio);
    }

    public function test_tecnico_puede_abrir_el_detalle_de_un_ticket_de_su_listado(): void
    {
        $tecnico = $this->usuario('tecnico');
        $ticket = $this->ticket(
            $this->usuario('solicitante'),
            'DETALLE',
            'Asignado',
            $tecnico
        );

        $this->actingAs($tecnico)
            ->get(route('tecnico.asignaciones.index'))
            ->assertOk()
            ->assertSee(route('tickets.show', $ticket));

        $this->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Seguimiento técnico');
    }

    public function test_muestra_mensaje_cuando_el_tecnico_no_tiene_asignaciones(): void
    {
        $tecnico = $this->usuario('tecnico');

        $this->actingAs($tecnico)
            ->get(route('tecnico.asignaciones.index'))
            ->assertOk()
            ->assertSee('No tienes tickets asignados actualmente.');
    }

    public function test_las_asignaciones_cerradas_siguen_apareciendo_en_el_listado(): void
    {
        $tecnico = $this->usuario('tecnico');
        $ticket = $this->ticket(
            $this->usuario('solicitante'),
            'CERRADO',
            'Cerrado',
            $tecnico
        );

        $this->actingAs($tecnico)
            ->get(route('tecnico.asignaciones.index'))
            ->assertOk()
            ->assertSee($ticket->folio)
            ->assertSee('Cerrado');
    }

    public function test_tecnico_inactivo_no_puede_consultar_mis_asignaciones(): void
    {
        $tecnicoInactivo = $this->usuario('tecnico', false);

        $this->actingAs($tecnicoInactivo)
            ->get(route('tecnico.asignaciones.index'))
            ->assertRedirect(route('login'));
    }
}
