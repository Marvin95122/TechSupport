<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_general_carga_catalogos_y_no_crea_una_cuenta_demo(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('categorias', ['nombre' => 'Hardware']);
        $this->assertDatabaseHas('categorias', ['nombre' => 'Software']);
        $this->assertDatabaseHas('prioridades', ['nombre' => 'Alta']);
        $this->assertDatabaseHas('estados_ticket', ['nombre' => 'Pendiente']);
        $this->assertDatabaseHas('estados_ticket', ['nombre' => 'Cerrado']);
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }
}
