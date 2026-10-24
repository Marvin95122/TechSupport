<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Equipo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EquiposRegistroConsultaTest extends TestCase
{
    use RefreshDatabase;

    private function administrador(): User
    {
        return User::factory()->create(['role' => 'administrador', 'is_active' => true]);
    }

    private function datos(array $cambios = []): array
    {
        return array_merge([
            'codigo' => 'TS-EQ-001',
            'tipo' => 'Computadora',
            'marca' => 'Dell',
            'modelo' => 'OptiPlex',
            'serie' => 'TS-SERIE-001',
            'estado' => 'operativo',
        ], $cambios);
    }

    public function test_visitante_no_puede_consultar_equipos(): void
    {
        $this->get(route('admin.equipos.index'))->assertRedirect(route('login'));
    }

    public function test_tecnico_y_solicitante_no_pueden_administrar_equipos(): void
    {
        foreach (['tecnico', 'solicitante'] as $role) {
            $user = User::factory()->create(['role' => $role, 'is_active' => true]);
            $this->actingAs($user)->get(route('admin.equipos.index'))->assertForbidden();
            $this->actingAs($user)->post(route('admin.equipos.store'), $this->datos(['codigo' => 'EQ-'.$role]))->assertForbidden();
        }
    }

    public function test_administrador_puede_registrar_y_consultar_equipo(): void
    {
        $this->actingAs($this->administrador())
            ->post(route('admin.equipos.store'), $this->datos())
            ->assertRedirect();

        $equipo = Equipo::where('codigo', 'TS-EQ-001')->firstOrFail();
        $this->assertTrue($equipo->activo);
        $this->get(route('admin.equipos.show', $equipo))
            ->assertOk()
            ->assertSee('TS-EQ-001')
            ->assertSee('Dell');
    }

    public function test_no_permite_codigo_duplicado(): void
    {
        $this->actingAs($this->administrador());
        $this->post(route('admin.equipos.store'), $this->datos())->assertRedirect();
        $this->post(route('admin.equipos.store'), $this->datos(['serie' => 'DISTINTA']))
            ->assertSessionHasErrors('codigo');
        $this->assertDatabaseCount('equipos', 1);
    }

    public function test_no_permite_serie_duplicada_cuando_se_proporciona(): void
    {
        $this->actingAs($this->administrador());
        $this->post(route('admin.equipos.store'), $this->datos())->assertRedirect();
        $this->post(route('admin.equipos.store'), $this->datos(['codigo' => 'TS-EQ-002']))
            ->assertSessionHasErrors('serie');
        $this->assertDatabaseCount('equipos', 1);
    }

    public function test_area_y_responsable_son_opcionales_y_se_pueden_asociar(): void
    {
        $administrador = $this->administrador();
        $responsable = User::factory()->create(['role' => 'tecnico', 'is_active' => true]);
        $area = Area::create(['nombre' => 'Sistemas']);
        $this->actingAs($administrador)
            ->post(route('admin.equipos.store'), $this->datos([
                'area_id' => $area->id,
                'responsable_id' => $responsable->id,
            ]))->assertRedirect();
        $this->assertDatabaseHas('equipos', [
            'codigo' => 'TS-EQ-001', 'area_id' => $area->id,
            'responsable_id' => $responsable->id,
        ]);
    }

    public function test_no_permite_responsable_inactivo(): void
    {
        $responsable = User::factory()->create(['role' => 'tecnico', 'is_active' => false]);
        $this->actingAs($this->administrador())
            ->post(route('admin.equipos.store'), $this->datos(['responsable_id' => $responsable->id]))
            ->assertSessionHasErrors('responsable_id');
    }

    public function test_listado_solo_muestra_equipos_activos(): void
    {
        Equipo::create($this->datos());
        Equipo::create($this->datos(['codigo' => 'TS-EQ-002', 'serie' => null, 'activo' => false]));
        $this->actingAs($this->administrador())
            ->get(route('admin.equipos.index'))
            ->assertOk()->assertSee('TS-EQ-001')->assertDontSee('TS-EQ-002');
    }

    public function test_admin_puede_crear_area_y_evita_duplicados(): void
    {
        $this->actingAs($this->administrador());
        $this->post(route('admin.areas.store'), ['nombre' => 'Sistemas'])
            ->assertRedirect(route('admin.equipos.create'));
        $this->post(route('admin.areas.store'), ['nombre' => 'Sistemas'])
            ->assertSessionHasErrors('nombre');
        $this->assertDatabaseCount('areas', 1);
    }
}
