<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\EstadoTicket;
use App\Models\Prioridad;
use App\Models\Ticket;
use App\Models\TicketObservacion;
use App\Models\User;
use Database\Seeders\CatalogosTicketsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReporteAtencionExperienciaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogosTicketsSeeder::class);
    }

    private function usuario(string $rol): User
    {
        return User::factory()->create([
            'role' => $rol,
            'is_active' => true,
        ]);
    }

    private function ticket(string $folio, User $solicitante): Ticket
    {
        return Ticket::create([
            'folio' => $folio,
            'solicitante_id' => $solicitante->id,
            'categoria_id' => Categoria::query()->where('nombre', 'Hardware')->firstOrFail()->id,
            'prioridad_id' => Prioridad::query()->where('nombre', 'Alta')->firstOrFail()->id,
            'estado_id' => EstadoTicket::query()->where('nombre', 'En proceso')->firstOrFail()->id,
            'asunto' => 'Revisión de equipo de prueba',
            'descripcion' => 'Se requiere revisar el funcionamiento del equipo y documentar la atención realizada.',
        ]);
    }

    public function test_reporte_muestra_graficas_y_acceso_al_expediente(): void
    {
        $administrador = $this->usuario('administrador');
        $solicitante = $this->usuario('solicitante');
        $ticket = $this->ticket('TS-REPORTE-ANALITICA-001', $solicitante);

        $this->actingAs($administrador)
            ->get(route('admin.reportes.atencion'))
            ->assertOk()
            ->assertSee('Tendencia de solicitudes')
            ->assertSee('Distribución por estado')
            ->assertSee('Solicitudes por categoría')
            ->assertSee('Solicitudes por prioridad')
            ->assertSee('data-chart-category="Hardware"', false)
            ->assertSee('data-chart-priority="Alta"', false)
            ->assertSee('Ver expediente')
            ->assertSee(route('admin.reportes.atencion.detalle', ['ticket' => $ticket->id]), false);
    }

    public function test_administrador_puede_consultar_expediente_y_cronologia_completa(): void
    {
        $administrador = $this->usuario('administrador');
        $solicitante = $this->usuario('solicitante');
        $ticket = $this->ticket('TS-REPORTE-HISTORIAL-001', $solicitante);

        TicketObservacion::create([
            'ticket_id' => $ticket->id,
            'usuario_id' => $administrador->id,
            'tipo' => 'observacion',
            'contenido' => 'Se revisó el equipo y se verificaron las conexiones.',
            'fecha' => now(),
        ]);

        $this->actingAs($administrador)
            ->get(route('admin.reportes.atencion.detalle', [
                'ticket' => $ticket->id,
                'estado_id' => $ticket->estado_id,
            ]))
            ->assertOk()
            ->assertSee('Expediente de atención')
            ->assertSee($ticket->folio)
            ->assertSee('Historial de atención')
            ->assertSee('Se revisó el equipo y se verificaron las conexiones.')
            ->assertSee(route('admin.reportes.atencion', ['estado_id' => $ticket->estado_id]), false);
    }

    public function test_version_de_impresion_incluye_analitica_del_periodo(): void
    {
        $administrador = $this->usuario('administrador');
        $solicitante = $this->usuario('solicitante');
        $this->ticket('TS-REPORTE-IMPRESION-ANALITICA', $solicitante);

        $this->actingAs($administrador)
            ->get(route('admin.reportes.atencion.imprimir'))
            ->assertOk()
            ->assertSee('Tendencia de solicitudes')
            ->assertSee('Solicitudes por categoría')
            ->assertSee('Solicitudes por prioridad');
    }

    public function test_expediente_del_reporte_es_exclusivo_del_administrador(): void
    {
        $solicitante = $this->usuario('solicitante');
        $ticket = $this->ticket('TS-REPORTE-PERMISOS-001', $solicitante);
        $ruta = route('admin.reportes.atencion.detalle', ['ticket' => $ticket->id]);

        $this->get($ruta)->assertRedirect(route('login'));

        $this->actingAs($this->usuario('tecnico'))
            ->get($ruta)
            ->assertForbidden();

        $this->actingAs($solicitante)
            ->get($ruta)
            ->assertForbidden();
    }

    public function test_administrador_inactivo_no_puede_acceder_a_las_funciones_del_reporte(): void
    {
        $solicitante = $this->usuario('solicitante');

        $ticket = $this->ticket(
            'TS-REPORTE-ADMIN-INACTIVO-001',
            $solicitante
        );

        $rutas = [
            route('admin.reportes.atencion'),
            route('admin.reportes.atencion.imprimir'),
            route('admin.reportes.atencion.exportar'),
            route('admin.reportes.atencion.exportar.excel'),
            route('admin.reportes.atencion.detalle', [
                'ticket' => $ticket->id,
            ]),
        ];

        foreach ($rutas as $ruta) {
            $administradorInactivo = User::factory()->create([
                'role' => 'administrador',
                'is_active' => false,
            ]);

            $this->actingAs($administradorInactivo)
                ->get($ruta)
                ->assertRedirect(route('login'))
                ->assertSessionHasErrors('email');

            $this->assertGuest();
        }
    }
}
