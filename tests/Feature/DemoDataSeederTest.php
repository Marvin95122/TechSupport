<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DemoDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_crea_datos_de_demostracion_relacionados_y_no_crea_usuarios(): void
    {
        $administrador = User::factory()->create([
            'role' => 'administrador',
            'is_active' => true,
        ]);

        $tecnico = User::factory()->create([
            'role' => 'tecnico',
            'is_active' => true,
        ]);

        $solicitante = User::factory()->create([
            'role' => 'solicitante',
            'is_active' => true,
        ]);

        $this->seed(DemoDataSeeder::class);

        $this->assertDatabaseCount('users', 3);
        $this->assertDatabaseCount('areas', 5);
        $this->assertDatabaseCount('equipos', 8);
        $this->assertDatabaseCount('tickets', 12);
        $this->assertDatabaseCount('cambios_estado', 26);
        $this->assertDatabaseCount('ticket_observaciones', 8);
        $this->assertDatabaseCount('ticket_soluciones', 8);

        $distribucion = DB::table('tickets')
            ->join('estados_ticket', 'tickets.estado_id', '=', 'estados_ticket.id')
            ->where('tickets.folio', 'like', 'DEMO-TS-%')
            ->select('estados_ticket.nombre', DB::raw('COUNT(tickets.id) AS total'))
            ->groupBy('estados_ticket.nombre')
            ->pluck('total', 'nombre')
            ->map(fn ($total): int => (int) $total)
            ->all();

        $this->assertEquals([
            'Pendiente' => 2,
            'Asignado' => 2,
            'En proceso' => 3,
            'Resuelto' => 2,
            'Cerrado' => 3,
        ], $distribucion);

        $this->assertDatabaseHas('tickets', [
            'folio' => 'DEMO-TS-003',
            'solicitante_id' => $solicitante->id,
            'tecnico_id' => $tecnico->id,
            'asignado_por_id' => $administrador->id,
        ]);

        $this->assertDatabaseHas('cambios_estado', [
            'ticket_id' => Ticket::query()->where('folio', 'DEMO-TS-012')->value('id'),
            'usuario_id' => $tecnico->id,
        ]);
    }

    public function test_ejecutar_el_seeder_dos_veces_no_duplica_datos(): void
    {
        User::factory()->create(['role' => 'administrador', 'is_active' => true]);
        User::factory()->create(['role' => 'tecnico', 'is_active' => true]);
        User::factory()->create(['role' => 'solicitante', 'is_active' => true]);

        $this->seed(DemoDataSeeder::class);
        $this->seed(DemoDataSeeder::class);

        $this->assertDatabaseCount('users', 3);
        $this->assertDatabaseCount('areas', 5);
        $this->assertDatabaseCount('equipos', 8);
        $this->assertDatabaseCount('tickets', 12);
        $this->assertDatabaseCount('cambios_estado', 26);
        $this->assertDatabaseCount('ticket_observaciones', 8);
        $this->assertDatabaseCount('ticket_soluciones', 8);
    }
}
