<?php

namespace App\Http\Controllers;

use App\Models\EstadoTicket;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /** HU-23 y HU-24: indicadores reales, limitados al alcance del usuario. */
    public function index(Request $request): View
    {
        $usuario = $request->user();

        $consulta = DB::table('tickets')
            ->join('estados_ticket', 'tickets.estado_id', '=', 'estados_ticket.id');

        $alcanceIndicadores = match ($usuario->role) {
            'administrador' => 'Vista general de todas las solicitudes',
            'tecnico' => 'Resumen exclusivo de tus tickets asignados',
            default => 'Resumen exclusivo de tus solicitudes',
        };

        if ($usuario->role === 'tecnico') {
            $consulta->where('tickets.tecnico_id', $usuario->id);
        } elseif ($usuario->role === 'solicitante') {
            $consulta->where('tickets.solicitante_id', $usuario->id);
        }

        /** @var Builder $consulta */
        $conteos = $consulta
            ->select('estados_ticket.nombre', DB::raw('COUNT(tickets.id) AS total'))
            ->groupBy('estados_ticket.nombre')
            ->pluck('total', 'nombre');

        $metricas = EstadoTicket::query()
            ->orderBy('orden')
            ->get(['id', 'nombre', 'orden'])
            ->map(static fn (EstadoTicket $estado): array => [
                'id' => $estado->id,
                'nombre' => $estado->nombre,
                'total' => (int) ($conteos[$estado->nombre] ?? 0),
            ]);

        return view('dashboard', compact('metricas', 'alcanceIndicadores'));
    }
}
