<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Carga los catálogos requeridos por los módulos de tickets y equipos.
     * No crea cuentas de acceso con credenciales de demostración.
     */
    public function run(): void
    {
        $this->call(CatalogosTicketsSeeder::class);
    }
}
