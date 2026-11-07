<?php

namespace App\Http\Controllers\Equipos;

use App\Http\Controllers\Controller;
use App\Models\Equipo;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EquipoHistorialController extends Controller
{
    /**
     * HU-20: reúne las incidencias relacionadas con un equipo.
     *
     * El administrador consulta todos los tickets del equipo.
     * El técnico debe tener al menos una asignación en ese equipo y solo consulta
     * los tickets que tiene asignados, evitando exponer solicitudes ajenas.
     * No se filtra por equipos activos: la baja lógica conserva el historial.
     */
    public function show(Request $request, Equipo $equipo): View
    {
        $usuario = $request->user();
        $esAdministrador = $usuario->role === 'administrador';

        $consulta = Ticket::query()
            ->where('equipo_id', $equipo->id);

        if (! $esAdministrador) {
            $tieneAcceso = (clone $consulta)
                ->where('tecnico_id', $usuario->id)
                ->exists();

            abort_unless(
                $usuario->role === 'tecnico' && $tieneAcceso,
                403,
                'No tienes tickets asignados relacionados con este equipo.'
            );

            $consulta->where('tecnico_id', $usuario->id);
        }

        $tickets = $consulta
            ->with(['estado', 'prioridad', 'solicitante', 'tecnico'])
            ->orderByDesc('creado_en')
            ->orderByDesc('id')
            ->paginate(10);

        return view('equipos.historial', compact(
            'equipo',
            'tickets',
            'esAdministrador'
        ));
    }
}
