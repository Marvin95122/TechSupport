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

class TicketsConsultaDetalleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogosTicketsSeeder::class);
    }

    private function usuario(string $rol = 'solicitante', bool $activo = true): User
    {
        return User::factory()->create(['role' => $rol, 'is_active' => $activo]);
    }

    private function ticket(User $solicitante, string $sufijo, array $cambios = []): Ticket
    {
        return Ticket::create(array_merge([
            'folio' => 'TS-TEST-'.$sufijo,
            'solicitante_id' => $solicitante->id,
            'equipo_id' => null,
            'categoria_id' => Categoria::where('nombre', 'Hardware')->firstOrFail()->id,
            'prioridad_id' => Prioridad::where('nombre', 'Alta')->firstOrFail()->id,
            'estado_id' => EstadoTicket::where('nombre', 'Pendiente')->firstOrFail()->id,
            'asunto' => 'Incidencia '.$sufijo,
            'descripcion' => 'La cámara de la unidad presenta un problema de conexión.',
        ], $cambios));
    }

    public function test_visitante_no_puede_consultar_listado_ni_detalle(): void
    {
        $ticket = $this->ticket($this->usuario(), 'VISITANTE');
        $this->get(route('tickets.index'))->assertRedirect(route('login'));
        $this->get(route('tickets.show', $ticket))->assertRedirect(route('login'));
    }

    public function test_tecnico_no_puede_consultar_los_tickets_en_el_sprint_4(): void
    {
        $ticket = $this->ticket($this->usuario(), 'TECNICO');
        $this->actingAs($this->usuario('tecnico'));
        $this->get(route('tickets.index'))->assertForbidden();
        $this->get(route('tickets.show', $ticket))->assertForbidden();
    }

    public function test_solicitante_solo_ve_sus_propias_solicitudes(): void
    {
        $propietario = $this->usuario();
        $otro = $this->usuario();
        $mio = $this->ticket($propietario, 'MIO');
        $ajeno = $this->ticket($otro, 'AJENO');
        $this->actingAs($propietario)->get(route('tickets.index'))
            ->assertOk()->assertSee($mio->folio)->assertDontSee($ajeno->folio)
            ->assertSee('Mis tickets');
    }

    public function test_solicitante_no_puede_abrir_detalle_ajeno_por_url(): void
    {
        $otro = $this->usuario();
        $ticket = $this->ticket($otro, 'PRIVADO');
        $this->actingAs($this->usuario())->get(route('tickets.show', $ticket))->assertForbidden();
    }

    public function test_solicitante_puede_ver_folio_descripcion_categoria_y_prioridad(): void
    {
        $propietario = $this->usuario();
        $ticket = $this->ticket($propietario, 'DETALLE');
        $this->actingAs($propietario)->get(route('tickets.show', $ticket))
            ->assertOk()->assertSee($ticket->folio)
            ->assertSee('La cámara de la unidad')
            ->assertSee('Hardware')->assertSee('Alta')->assertSee('Pendiente');
    }

    public function test_administrador_puede_listar_todas_las_solicitudes(): void
    {
        $primero = $this->ticket($this->usuario(), 'A1');
        $segundo = $this->ticket($this->usuario(), 'A2');
        $this->actingAs($this->usuario('administrador'))
            ->get(route('tickets.index'))->assertOk()
            ->assertSee($primero->folio)->assertSee($segundo->folio)
            ->assertSee('Tickets registrados');
    }

    public function test_administrador_puede_abrir_el_detalle_de_cualquier_solicitud(): void
    {
        $ticket = $this->ticket($this->usuario(), 'ADMIN');
        $this->actingAs($this->usuario('administrador'))
            ->get(route('tickets.show', $ticket))->assertOk()->assertSee($ticket->folio);
    }

    public function test_busqueda_y_filtro_de_estado_funcionan_sin_mostrar_tickets_ajenos(): void
    {
        $propietario = $this->usuario();
        $mio = $this->ticket($propietario, 'CAMARA');
        $otro = $this->ticket($propietario, 'RADIO');
        $ajeno = $this->ticket($this->usuario(), 'CAMARA-AJENA');
        $pendiente = EstadoTicket::where('nombre', 'Pendiente')->firstOrFail();
        $this->actingAs($propietario)
            ->get(route('tickets.index', ['buscar' => 'CAMARA', 'estado' => $pendiente->id]))
            ->assertOk()->assertSee($mio->folio)
            ->assertDontSee($otro->folio)->assertDontSee($ajeno->folio);
    }

    public function test_la_lista_se_pagina_sin_exponer_datos_ajenos(): void
    {
        $propietario = $this->usuario();
        for ($n = 1; $n <= 12; $n++) {
            $this->ticket($propietario, sprintf('P%02d', $n));
        }
        $ajeno = $this->ticket($this->usuario(), 'AJENO-PAGINA');
        $this->actingAs($propietario)->get(route('tickets.index', ['page' => 2]))
            ->assertOk()->assertDontSee($ajeno->folio);
    }

    public function test_equipo_desactivado_se_conserva_en_la_ficha_historica(): void
    {
        $equipo = Equipo::create([
            'codigo' => 'EQ-S4-C2-001', 'tipo' => 'Cámara', 'marca' => 'Hikvision',
            'modelo' => 'Prueba', 'estado' => 'mantenimiento', 'activo' => false,
        ]);
        $solicitante = $this->usuario();
        $ticket = $this->ticket($solicitante, 'EQUIPO-BAJA', ['equipo_id' => $equipo->id]);
        $this->actingAs($solicitante)->get(route('tickets.show', $ticket))
            ->assertOk()->assertSee('EQ-S4-C2-001')->assertSee('Equipo inactivo');
    }

    public function test_usuario_desactivado_no_puede_consultar_tickets(): void
    {
        $ticket = $this->ticket($this->usuario(), 'DESACTIVADO');
        $inactivo = $this->usuario('solicitante', false);
        $this->actingAs($inactivo)->get(route('tickets.index'))->assertRedirect(route('login'));
        $this->actingAs($inactivo)->get(route('tickets.show', $ticket))->assertRedirect(route('login'));
    }

    public function test_ticket_no_existente_devuelve_404(): void
    {
        $this->actingAs($this->usuario('administrador'))
            ->get(route('tickets.show', 99999999))->assertNotFound();
    }
}
