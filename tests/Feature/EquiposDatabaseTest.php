<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Equipo;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EquiposDatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_areas_and_equipos_have_the_documented_columns(): void
    {
        $this->assertTrue(Schema::hasTable('areas'));
        $this->assertTrue(Schema::hasTable('equipos'));
        $this->assertTrue(Schema::hasColumns('areas', ['id', 'nombre']));
        $this->assertTrue(Schema::hasColumns('equipos', [
            'id', 'codigo', 'area_id', 'responsable_id',
            'tipo', 'marca', 'modelo', 'serie', 'estado', 'activo',
        ]));
    }

    public function test_equipo_can_belong_to_an_area_and_user(): void
    {
        $area = Area::create(['nombre' => 'Sistemas']);
        $user = User::factory()->create(['role' => 'tecnico', 'is_active' => true]);
        $equipo = Equipo::create([
            'codigo' => 'EQ-100',
            'area_id' => $area->id,
            'responsable_id' => $user->id,
            'tipo' => 'Computadora',
            'marca' => 'Dell',
            'modelo' => 'Latitude',
            'serie' => 'SER-100',
            'estado' => 'operativo',
        ]);

        $this->assertTrue($equipo->area->is($area));
        $this->assertTrue($equipo->responsable->is($user));
        $this->assertTrue($equipo->activo);
        $this->assertCount(1, $area->equipos);
    }

    public function test_area_and_responsable_are_optional(): void
    {
        $equipo = Equipo::create([
            'codigo' => 'EQ-101',
            'tipo' => 'Impresora',
            'marca' => 'HP',
            'modelo' => 'LaserJet',
            'serie' => null,
            'estado' => 'operativo',
        ]);

        $this->assertNull($equipo->area_id);
        $this->assertNull($equipo->responsable_id);
        $this->assertDatabaseHas('equipos', ['id' => $equipo->id]);
    }

    public function test_codigo_is_unique(): void
    {
        $datos = [
            'codigo' => 'EQ-102',
            'tipo' => 'Monitor',
            'marca' => 'LG',
            'modelo' => '24MK',
            'estado' => 'operativo',
        ];

        Equipo::create($datos);
        $this->expectException(QueryException::class);
        Equipo::create($datos);
    }

    public function test_deactivation_does_not_delete_equipment(): void
    {
        $equipo = Equipo::create([
            'codigo' => 'EQ-103',
            'tipo' => 'Router',
            'marca' => 'Cisco',
            'modelo' => 'ISR',
            'estado' => 'operativo',
        ]);

        $equipo->update(['activo' => false]);
        $this->assertFalse($equipo->fresh()->activo);
        $this->assertDatabaseHas('equipos', ['id' => $equipo->id, 'activo' => 0]);
    }
}
