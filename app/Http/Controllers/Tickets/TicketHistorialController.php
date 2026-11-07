<?php

namespace App\Http\Controllers\Tickets;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Índice general del historial de atención para administrador y técnicos. */
class TicketHistorialController extends Controller
{
    public function index(Request $request): View
    {
        $usuario = $request->user();
        $esAdministrador = $usuario->role === 'administrador';
        abort_unless($esAdministrador || $usuario->role === 'tecnico', 403);

        $entrada = $request->query('buscar', '');
        $buscar = is_string($entrada) ? mb_substr(trim($entrada), 0, 120) : '';

        $consulta = Ticket::query()
            ->with(['solicitante', 'tecnico', 'equipo', 'estado'])
            ->withCount(['cambiosEstado', 'observaciones', 'soluciones']);

        if (! $esAdministrador) {
            $consulta->where('tecnico_id', $usuario->id);
        }

        if ($buscar !== '') {
            $consulta->where(function ($query) use ($buscar): void {
                $termino = '%'.$buscar.'%';
                $query->where('folio', 'like', $termino)
                    ->orWhere('asunto', 'like', $termino)
                    ->orWhereHas('solicitante', function ($relacion) use ($termino): void {
                        $relacion->where('name', 'like', $termino);
                    })
                    ->orWhereHas('equipo', function ($relacion) use ($termino): void {
                        $relacion->where('codigo', 'like', $termino);
                    });
            });
        }

        $tickets = $consulta
            ->orderByDesc('creado_en')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('tickets.historial', compact('tickets', 'buscar', 'esAdministrador'));
    }
}
