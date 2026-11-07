<?php

namespace App\Http\Controllers\Tickets;

use App\Http\Controllers\Controller;
use App\Models\EstadoTicket;
use App\Models\Ticket;
use App\Models\TicketSolucion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TicketSolucionController extends Controller
{
    /**
     * HU-18: conserva el diagnóstico, las acciones ejecutadas y la solución aplicada.
     * Cada envío crea evidencia nueva; no se actualizan ni eliminan soluciones previas.
     */
    public function store(Request $request, Ticket $ticket): RedirectResponse
    {
        $datos = $request->validate([
            'diagnostico' => ['required', 'string', 'min:3', 'max:5000'],
            'acciones_realizadas' => ['required', 'string', 'min:3', 'max:5000'],
            'solucion' => ['required', 'string', 'min:3', 'max:5000'],
        ], [
            'diagnostico.required' => 'Describe el diagnóstico del problema.',
            'acciones_realizadas.required' => 'Describe las acciones ejecutadas.',
            'solucion.required' => 'Describe la solución aplicada.',
            '*.min' => 'Cada apartado debe contener al menos 3 caracteres.',
            '*.max' => 'Cada apartado no puede superar los 5000 caracteres.',
        ]);

        DB::transaction(function () use ($request, $ticket, $datos): void {
            $ticketActual = Ticket::query()
                ->whereKey($ticket->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless(
                (int) $ticketActual->tecnico_id === (int) $request->user()->id,
                403,
                'Solo el técnico asignado puede registrar la solución de este ticket.'
            );

            $estado = EstadoTicket::query()->find($ticketActual->estado_id);

            if (! $estado || ! in_array($estado->nombre, ['En proceso', 'Resuelto'], true)) {
                throw ValidationException::withMessages([
                    'diagnostico' => 'El diagnóstico y la solución solo pueden registrarse cuando el ticket está En proceso o Resuelto.',
                ]);
            }

            TicketSolucion::create([
                'ticket_id' => $ticketActual->id,
                'usuario_id' => $request->user()->id,
                'diagnostico' => trim($datos['diagnostico']),
                'acciones_realizadas' => trim($datos['acciones_realizadas']),
                'solucion' => trim($datos['solucion']),
                'fecha' => now(),
            ]);
        }, 3);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', 'El diagnóstico y la solución se registraron correctamente.');
    }
}
