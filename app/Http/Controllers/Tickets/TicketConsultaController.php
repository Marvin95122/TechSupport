<?php

namespace App\Http\Controllers\Tickets;

use App\Http\Controllers\Controller;
use App\Models\EstadoTicket;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Sprint 4 HU-10/HU-12: lectura sin asignaciones ni cambios de estado. */
class TicketConsultaController extends Controller
{
    public function index(Request $request): View
    {
        $usuario = $request->user();
        $esAdministrador = $usuario->role === 'administrador';
        // En el Sprint 4, únicamente el administrador y el solicitante consultan tickets.
        abort_unless($esAdministrador || $usuario->role === 'solicitante', 403);

        $entradaBuscar = $request->query('buscar', '');
        $buscar = is_string($entradaBuscar) ? mb_substr(trim($entradaBuscar), 0, 120) : '';
        $estado = $request->query('estado', '');
        $estado = is_string($estado) && ctype_digit($estado) ? $estado : '';

        $tickets = Ticket::query()
            ->with(['solicitante', 'categoria', 'prioridad', 'estado', 'equipo'])
            ->when(! $esAdministrador, function ($query) use ($usuario): void {
                $query->where('solicitante_id', $usuario->id);
            })
            ->when($buscar !== '', function ($query) use ($buscar): void {
                $query->where(function ($q) use ($buscar): void {
                    $q->where('folio', 'like', '%'.$buscar.'%')
                      ->orWhere('asunto', 'like', '%'.$buscar.'%');
                });
            })
            ->when($estado !== '', function ($query) use ($estado): void {
                $query->where('estado_id', (int) $estado);
            })
            ->orderByDesc('creado_en')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $estados = EstadoTicket::query()->orderBy('orden')->get();

        return view('tickets.index', compact('tickets', 'estados', 'buscar', 'estado', 'esAdministrador'));
    }

    public function show(Request $request, Ticket $ticket): View
    {
        $usuario = $request->user();
        $esAdministrador = $usuario->role === 'administrador';

        // Se aplica en servidor incluso si se conoce la URL/ID de otro ticket.
        abort_unless(
            $esAdministrador ||
            ($usuario->role === 'solicitante' && (int) $ticket->solicitante_id === (int) $usuario->id),
            403,
            'No tienes permisos para consultar esta solicitud.'
        );

        // Las relaciones se consultan aunque un equipo haya sido dado de baja.
        $ticket->load(['solicitante', 'categoria', 'prioridad', 'estado', 'equipo']);

        return view('tickets.show', compact('ticket', 'esAdministrador'));
    }
}
