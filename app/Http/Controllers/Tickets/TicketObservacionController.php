<?php

namespace App\Http\Controllers\Tickets;

use App\Http\Controllers\Controller;
use App\Models\EstadoTicket;
use App\Models\Ticket;
use App\Models\TicketObservacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TicketObservacionController extends Controller
{
    /**
     * HU-17: agrega una observación o actividad a la bitácora del ticket.
     * La operación es de solo inserción; nunca reemplaza registros anteriores.
     */
    public function store(Request $request, Ticket $ticket): RedirectResponse
    {
        $datos = $request->validate([
            'tipo' => ['required', 'string', Rule::in(['observacion', 'actividad'])],
            'contenido' => ['required', 'string', 'min:3', 'max:5000'],
        ], [
            'tipo.required' => 'Selecciona el tipo de registro.',
            'tipo.in' => 'El tipo de registro seleccionado no es válido.',
            'contenido.required' => 'Escribe el contenido de la observación o actividad.',
            'contenido.min' => 'El contenido debe tener al menos 3 caracteres.',
            'contenido.max' => 'El contenido no puede superar los 5000 caracteres.',
        ]);

        DB::transaction(function () use ($request, $ticket, $datos): void {
            $ticketActual = Ticket::query()
                ->whereKey($ticket->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless(
                (int) $ticketActual->tecnico_id === (int) $request->user()->id,
                403,
                'Solo el técnico asignado puede registrar actividad en este ticket.'
            );

            $estado = EstadoTicket::query()->find($ticketActual->estado_id);

            if (! $estado || $estado->nombre === 'Cerrado') {
                throw ValidationException::withMessages([
                    'contenido' => 'No se pueden agregar observaciones a un ticket cerrado.',
                ]);
            }

            TicketObservacion::create([
                'ticket_id' => $ticketActual->id,
                'usuario_id' => $request->user()->id,
                'tipo' => $datos['tipo'],
                'contenido' => trim($datos['contenido']),
                'fecha' => now(),
            ]);
        }, 3);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', 'El registro se agregó correctamente a la bitácora del ticket.');
    }
}
