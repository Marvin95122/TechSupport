<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Equipo;
use App\Models\EstadoTicket;
use App\Models\Prioridad;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\CatalogosTicketsSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TicketsEstructuraDatabaseTest extends TestCase
{
    use RefreshDatabase;

    private function ticketData(array $changes = []): array
    {
        $this->seed(CatalogosTicketsSeeder::class);

        return array_merge([
            'folio' => 'TS-2026-000001',
            'solicitante_id' => User::factory()->create([
                'role' => 'solicitante',
                'is_active' => true,
            ])->id,
            'equipo_id' => null,
            'categoria_id' => Categoria::where('nombre', 'Hardware')->firstOrFail()->id,
            'prioridad_id' => Prioridad::where('nombre', 'Media')->firstOrFail()->id,
            'estado_id' => EstadoTicket::where('nombre', 'Pendiente')->firstOrFail()->id,
            'asunto' => 'La computadora no inicia',
            'descripcion' => 'El equipo se apaga al encender.',
        ], $changes);
    }

    public function test_existen_tablas_y_columnas_documentadas(): void
    {
        foreach (['categorias', 'prioridades', 'estados_ticket', 'tickets'] as $table) {
            $this->assertTrue(Schema::hasTable($table), 'Falta tabla: '.$table);
        }

        foreach (['folio', 'solicitante_id', 'equipo_id', 'categoria_id', 'prioridad_id',
            'estado_id', 'tecnico_id', 'asignado_por_id', 'asunto', 'descripcion',
            'creado_en', 'asignado_en', 'resuelto_en', 'cerrado_en'] as $column) {
            $this->assertTrue(Schema::hasColumn('tickets', $column), 'Falta columna: '.$column);
        }
    }

    public function test_catalogos_se_crean_sin_duplicarse_al_sembrar_dos_veces(): void
    {
        $this->seed(CatalogosTicketsSeeder::class);
        $this->seed(CatalogosTicketsSeeder::class);

        $this->assertSame(6, Categoria::count());
        $this->assertSame(4, Prioridad::count());
        $this->assertSame(5, EstadoTicket::count());
        $this->assertDatabaseHas('prioridades', ['nivel' => 4, 'nombre' => 'Crítica']);
        $this->assertDatabaseHas('estados_ticket', ['orden' => 1, 'nombre' => 'Pendiente']);
    }

    public function test_ticket_tiene_relaciones_y_equipo_opcional(): void
    {
        $ticket = Ticket::create($this->ticketData());
        $ticket->refresh()->load(['solicitante', 'categoria', 'prioridad', 'estado', 'equipo']);

        $this->assertSame('solicitante', $ticket->solicitante->role);
        $this->assertSame('Hardware', $ticket->categoria->nombre);
        $this->assertSame('Media', $ticket->prioridad->nombre);
        $this->assertSame('Pendiente', $ticket->estado->nombre);
        $this->assertNull($ticket->equipo);
        $this->assertNotNull($ticket->creado_en);
    }

    public function test_ticket_puede_relacionarse_con_equipo_existente(): void
    {
        $equipo = Equipo::create([
            'codigo' => 'TS-EQ-S4-01', 'tipo' => 'Computadora',
            'marca' => 'Dell', 'modelo' => 'OptiPlex', 'serie' => null,
            'estado' => 'operativo', 'activo' => true,
        ]);

        $ticket = Ticket::create($this->ticketData(['equipo_id' => $equipo->id]));
        $this->assertSame($equipo->id, $ticket->equipo->id);
    }

    public function test_folio_no_admite_duplicados(): void
    {
        Ticket::create($this->ticketData());

        $this->expectException(QueryException::class);
        Ticket::create($this->ticketData(['folio' => 'TS-2026-000001']));
    }
}
