<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\EstadoTicket;
use App\Models\Prioridad;
use Illuminate\Database\Seeder;

class CatalogosTicketsSeeder extends Seeder
{
    public function run(): void
    {
        // Catálogo inicial basado en los ejemplos del protocolo; revisable con el PO.
        foreach (['Hardware', 'Software', 'Red', 'Periféricos', 'Accesos', 'Configuración'] as $nombre) {
            Categoria::firstOrCreate(['nombre' => $nombre], ['activo' => true]);
        }

        // Propuesta de Tabla 3.6: bajo a crítico, sin cambiar registros existentes.
        foreach ([1 => 'Baja', 2 => 'Media', 3 => 'Alta', 4 => 'Crítica'] as $nivel => $nombre) {
            Prioridad::firstOrCreate(['nivel' => $nivel], ['nombre' => $nombre]);
        }

        // Sprint 4 solo utilizará Pendiente. Las transiciones llegan en Sprint 5.
        foreach ([1 => 'Pendiente', 2 => 'Asignado', 3 => 'En proceso', 4 => 'Resuelto', 5 => 'Cerrado'] as $orden => $nombre) {
            EstadoTicket::firstOrCreate(['orden' => $orden], ['nombre' => $nombre]);
        }
    }
}
