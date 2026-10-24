<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Equipo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EquiposEdicionBajaTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'administrador', 'is_active' => true]);
    }

    private function equipo(array $atributos = []): Equipo
    {
        return Equipo::create(array_merge([
            'codigo' => 'TS-EQ-001',
            'tipo' => 'Computadora',
            'marca' => 'Dell',
            'modelo' => 'OptiPlex',
            'serie' => 'SERIE-001',
            'estado' => 'operativo',
            'activo' => true,
        ], $atributos));
    }

    private function datos(array $cambios = []): array
    {
        return array_merge([
            'codigo' => 'TS-EQ-001',
            'tipo' => 'Computadora',
            'marca' => 'Dell',
            'modelo' => 'Latitude',
            'serie' => 'SERIE-001',
            'estado' => 'mantenimiento',
        ], $cambios);
    }

    public function test_administrador_puede_abrir_editar_y_actualizar_equipo(): void
    {
        $equipo = $this->equipo();
        $this->actingAs($this->admin())
            ->get(route('admin.equipos.edit', $equipo))
            ->assertOk()
            ->assertSee('Editar equipo');
        $this->put(route('admin.equipos.update', $equipo), $this->datos())
            ->assertRedirect(route('admin.equipos.show', $equipo));
        $this->assertDatabaseHas('equipos', ['id' => $equipo->id, 'modelo' => 'Latitude', 'estado' => 'mantenimiento']);
    }

    public function test_puede_mantener_el_codigo_y_serie_propios_y_quitar_campos_opcionales(): void
    {
        $area = Area::create(['nombre' => 'Sistemas']);
        $responsable = User::factory()->create(['role' => 'tecnico', 'is_active' => true]);
        $equipo = $this->equipo(['area_id' => $area->id, 'responsable_id' => $responsable->id]);
        $this->actingAs($this->admin())
            ->put(route('admin.equipos.update', $equipo), $this->datos([
                'serie' => '', 'area_id' => '', 'responsable_id' => '',
            ]))->assertRedirect();
        $this->assertDatabaseHas('equipos', [
            'id' => $equipo->id, 'codigo' => 'TS-EQ-001', 'serie' => null,
            'area_id' => null, 'responsable_id' => null,
        ]);
    }

    public function test_no_permite_codigo_duplicado_al_editar(): void
    {
        $equipo = $this->equipo();
        $this->equipo(['codigo' => 'TS-EQ-002', 'serie' => 'SERIE-002']);
        $this->actingAs($this->admin())
            ->put(route('admin.equipos.update', $equipo), $this->datos(['codigo' => 'TS-EQ-002']))
            ->assertSessionHasErrors('codigo');
        $this->assertSame('TS-EQ-001', $equipo->fresh()->codigo);
    }

    public function test_no_permite_serie_duplicada_al_editar(): void
    {
        $equipo = $this->equipo();
        $this->equipo(['codigo' => 'TS-EQ-002', 'serie' => 'SERIE-002']);
        $this->actingAs($this->admin())
            ->put(route('admin.equipos.update', $equipo), $this->datos(['serie' => 'SERIE-002']))
            ->assertSessionHasErrors('serie');
    }

    public function test_no_permite_responsable_inactivo_al_editar(): void
    {
        $equipo = $this->equipo();
        $responsable = User::factory()->create(['role' => 'tecnico', 'is_active' => false]);
        $this->actingAs($this->admin())
            ->put(route('admin.equipos.update', $equipo), $this->datos(['responsable_id' => $responsable->id]))
            ->assertSessionHasErrors('responsable_id');
    }

    public function test_cuenta_no_administradora_no_puede_editar_ni_desactivar(): void
    {
        $equipo = $this->equipo();
        foreach (['tecnico', 'solicitante'] as $rol) {
            $usuario = User::factory()->create(['role' => $rol, 'is_active' => true]);
            $this->actingAs($usuario)
                ->get(route('admin.equipos.edit', $equipo))->assertForbidden();
            $this->put(route('admin.equipos.update', $equipo), $this->datos())->assertForbidden();
            $this->patch(route('admin.equipos.deactivate', $equipo))->assertForbidden();
        }
        $this->assertTrue($equipo->fresh()->activo);
    }

    public function test_visitante_no_puede_editar_ni_desactivar(): void
    {
        $equipo = $this->equipo();
        $this->get(route('admin.equipos.edit', $equipo))->assertRedirect(route('login'));
        $this->patch(route('admin.equipos.deactivate', $equipo))->assertRedirect(route('login'));
    }

    public function test_desactivar_equipo_lo_conserva_y_oculta_del_listado_activo(): void
    {
        $equipo = $this->equipo();
        $this->actingAs($this->admin())
            ->patch(route('admin.equipos.deactivate', $equipo))
            ->assertRedirect(route('admin.equipos.show', $equipo));
        $this->assertDatabaseHas('equipos', ['id' => $equipo->id, 'activo' => false]);
        $this->get(route('admin.equipos.index'))->assertDontSee('TS-EQ-001');
        $this->get(route('admin.equipos.show', $equipo))->assertOk()->assertSee('Inactivo');
    }

    public function test_equipo_desactivado_no_puede_editarse(): void
    {
        $equipo = $this->equipo(['activo' => false]);
        $this->actingAs($this->admin())
            ->get(route('admin.equipos.edit', $equipo))->assertNotFound();
        $this->put(route('admin.equipos.update', $equipo), $this->datos())->assertNotFound();
    }

    public function test_no_se_puede_modificar_activo_por_el_formulario_de_edicion(): void
    {
        $equipo = $this->equipo();
        $this->actingAs($this->admin())
            ->put(route('admin.equipos.update', $equipo), $this->datos(['activo' => false]))
            ->assertRedirect();
        $this->assertTrue($equipo->fresh()->activo);
    }
}
