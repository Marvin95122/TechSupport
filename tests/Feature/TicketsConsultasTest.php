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

class TicketsConsultasTest extends TestCase
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

    private function equipo(string $codigo): Equipo
    {
        return Equipo::create([
            'codigo' => $codigo,
            'tipo' => 'Computadora',
            'marca' => 'Dell',
            'modelo' => 'OptiPlex',
            'estado' => 'operativo',
            'activo' => true,
        ]);
    }

    private function ticket(
        User $solicitante,
        string $sufijo,
        ?User $tecnico = null,
        ?Equipo $equipo = null,
        string $estado = 'Pendiente',
        string $categoria = 'Hardware',
        string $prioridad = 'Alta'
    ): Ticket {
        $ticket = Ticket::create([
            'folio' => 'TS-HU21-'.$sufijo,
            'solicitante_id' => $solicitante->id,
            'equipo_id' => $equipo?->id,
            'categoria_id' => Categoria::where('nombre', $categoria)->firstOrFail()->id,
            'prioridad_id' => Prioridad::where('nombre', $prioridad)->firstOrFail()->id,
            'estado_id' => EstadoTicket::where('nombre', $estado)->firstOrFail()->id,
            'asunto' => 'Incidencia '.$sufijo,
            'descripcion' => 'El recurso tecnológico presenta una falla que requiere atención.',
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

    public function test_administrador_busca_por_folio_solicitante_tecnico_y_equipo(): void
    {
        $administrador = $this->usuario('administrador');
        $solicitante = $this->usuario('solicitante', true, [
            'name' => 'María Solís', 'email' => 'maria.solis@example.test',
        ]);
        $tecnico = $this->usuario('tecnico', true, [
            'name' => 'Ricardo Pérez', 'email' => 'ricardo.perez@example.test',
        ]);
        $equipo = $this->equipo('EQ-RED-01');
        $ticket = $this->ticket($solicitante, 'BUSQUEDA', $tecnico, $equipo, 'Asignado');

        $this->actingAs($administrador)
            ->get(route('tickets.index', ['buscar' => $ticket->folio]))
            ->assertOk()->assertSee($ticket->folio);

        $this->get(route('tickets.index', ['buscar' => 'María Solís']))
            ->assertOk()->assertSee($ticket->folio);

        $this->get(route('tickets.index', ['buscar' => 'Ricardo Pérez']))
            ->assertOk()->assertSee($ticket->folio);

        $this->get(route('tickets.index', ['buscar' => 'EQ-RED-01']))
            ->assertOk()->assertSee($ticket->folio);
    }

    public function test_tecnico_solo_consulta_y_busca_dentro_de_sus_tickets_asignados(): void
    {
        $tecnico = $this->usuario('tecnico');
        $otroTecnico = $this->usuario('tecnico');
        $propio = $this->ticket(
            $this->usuario('solicitante', true, ['name' => 'Solicitante Propio']),
            'PROPIO', $tecnico, $this->equipo('EQ-OWN-01'), 'En proceso'
        );
        $ajeno = $this->ticket(
            $this->usuario('solicitante', true, ['name' => 'Solicitante Ajeno']),
            'AJENO', $otroTecnico, $this->equipo('EQ-OTHER-01'), 'Asignado'
        );

        $this->actingAs($tecnico)
            ->get(route('tickets.index'))
            ->assertOk()->assertSee($propio->folio)->assertDontSee($ajeno->folio);

        $this->get(route('tickets.index', ['buscar' => 'Solicitante Propio']))
            ->assertOk()->assertSee($propio->folio)->assertDontSee($ajeno->folio);

        $this->get(route('tickets.index', ['buscar' => 'EQ-OTHER-01']))
            ->assertOk()->assertDontSee($ajeno->folio);
    }

    public function test_filtros_combinados_por_categoria_prioridad_estado_y_fecha(): void
    {
        $administrador = $this->usuario('administrador');
        $correcto = $this->ticket($this->usuario(), 'FILTRO-OK', null, null, 'Pendiente', 'Hardware', 'Alta');
        $incorrecto = $this->ticket($this->usuario(), 'FILTRO-NO', null, null, 'Resuelto', 'Software', 'Baja');
        $correcto->forceFill(['creado_en' => now()->subDays(2)])->save();
        $incorrecto->forceFill(['creado_en' => now()->subDays(12)])->save();

        $this->actingAs($administrador)
            ->get(route('tickets.index', [
                'categoria' => Categoria::where('nombre', 'Hardware')->firstOrFail()->id,
                'prioridad' => Prioridad::where('nombre', 'Alta')->firstOrFail()->id,
                'estado' => EstadoTicket::where('nombre', 'Pendiente')->firstOrFail()->id,
                'desde' => now()->subDays(5)->toDateString(),
                'hasta' => now()->toDateString(),
            ]))
            ->assertOk()->assertSee($correcto->folio)->assertDontSee($incorrecto->folio);
    }

    public function test_filtro_hasta_funciona_sin_fecha_desde(): void
    {
        $administrador = $this->usuario('administrador');
        $anterior = $this->ticket($this->usuario(), 'HASTA-ANTERIOR');
        $actual = $this->ticket($this->usuario(), 'HASTA-ACTUAL');
        $anterior->forceFill(['creado_en' => now()->subDays(3)])->save();
        $actual->forceFill(['creado_en' => now()])->save();

        $this->actingAs($administrador)
            ->get(route('tickets.index', ['hasta' => now()->subDay()->toDateString()]))
            ->assertOk()
            ->assertSee($anterior->folio)
            ->assertDontSee($actual->folio);
    }

    public function test_fecha_final_anterior_a_fecha_inicial_muestra_error(): void
    {
        $administrador = $this->usuario('administrador');

        $this->actingAs($administrador)
            ->get(route('tickets.index', [
                'desde' => now()->toDateString(),
                'hasta' => now()->subDay()->toDateString(),
            ]))
            ->assertSessionHasErrors('hasta');
    }

    public function test_busqueda_de_tecnicos_devuelve_solo_coincidencias_activas(): void
    {
        $administrador = $this->usuario('administrador');
        $activo = $this->usuario('tecnico', true, ['name' => 'Marta Técnica', 'email' => 'marta@example.test']);
        $this->usuario('tecnico', false, ['name' => 'Marta Inactiva', 'email' => 'marta.inactiva@example.test']);
        $this->usuario('solicitante', true, ['name' => 'Marta Solicitante', 'email' => 'marta.solicitante@example.test']);

        $this->actingAs($administrador)
            ->getJson(route('admin.tecnicos.search', ['q' => 'Marta']))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment(['id' => $activo->id, 'name' => 'Marta Técnica']);
    }

    public function test_solo_administrador_puede_consultar_el_busqueda_de_tecnicos(): void
    {
        $tecnico = $this->usuario('tecnico');
        $this->actingAs($tecnico)
            ->getJson(route('admin.tecnicos.search', ['q' => 'Ma']))
            ->assertForbidden();
    }

    public function test_historial_general_respeta_el_alcance_del_tecnico(): void
    {
        $tecnico = $this->usuario('tecnico');
        $otroTecnico = $this->usuario('tecnico');
        $propio = $this->ticket($this->usuario(), 'HIST-OWN', $tecnico, null, 'Asignado');
        $ajeno = $this->ticket($this->usuario(), 'HIST-OTHER', $otroTecnico, null, 'Asignado');

        $this->actingAs($tecnico)
            ->get(route('historial.index'))
            ->assertOk()->assertSee($propio->folio)->assertDontSee($ajeno->folio);

        $this->actingAs($this->usuario('administrador'))
            ->get(route('historial.index'))
            ->assertOk()->assertSee($propio->folio)->assertSee($ajeno->folio);
    }

    public function test_solicitante_no_puede_abrir_el_indice_general_del_historial(): void
    {
        $this->actingAs($this->usuario('solicitante'))
            ->get(route('historial.index'))
            ->assertForbidden();
    }

    public function test_panel_administrativo_muestra_conteos_reales_por_estado(): void
    {
        $this->ticket($this->usuario(), 'DASH-PEND', null, null, 'Pendiente');
        $this->ticket($this->usuario(), 'DASH-ASSIGN', $this->usuario('tecnico'), null, 'Asignado');
        $this->ticket($this->usuario(), 'DASH-PROCESS', $this->usuario('tecnico'), null, 'En proceso');
        $this->ticket($this->usuario(), 'DASH-RESOLVED', $this->usuario('tecnico'), null, 'Resuelto');
        $this->ticket($this->usuario(), 'DASH-CLOSED', $this->usuario('tecnico'), null, 'Cerrado');

        $this->actingAs($this->usuario('administrador'))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Indicadores generales')
            ->assertSee('data-indicator="Pendiente:1"', false)
            ->assertSee('data-indicator="Asignado:1"', false)
            ->assertSee('data-indicator="En proceso:1"', false)
            ->assertSee('data-indicator="Resuelto:1"', false)
            ->assertSee('data-indicator="Cerrado:1"', false);
    }

    public function test_panel_del_tecnico_cuenta_unicamente_sus_tickets_asignados(): void
    {
        $tecnico = $this->usuario('tecnico');
        $otroTecnico = $this->usuario('tecnico');
        $this->ticket($this->usuario(), 'DASH-TECH-OWN', $tecnico, null, 'En proceso');
        $this->ticket($this->usuario(), 'DASH-TECH-OTHER', $otroTecnico, null, 'En proceso');

        $this->actingAs($tecnico)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Mi carga de trabajo')
            ->assertSee('data-indicator="En proceso:1"', false)
            ->assertSee('data-indicator="Pendiente:0"', false);
    }
}
