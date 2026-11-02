<?php

namespace App\Http\Controllers\Tickets;

use App\Http\Controllers\Controller;
use App\Models\CambioEstado;
use App\Models\EstadoTicket;
use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TicketEstadoController extends Controller
{
    /**
     * HU-14: permite al tecnico responsable avanzar el estado de su ticket.
     *
     * Las transiciones Pendiente -> Asignado pertenecen a HU-13.
     */
    private const TRANSICIONES = [
        'Asignado' => 'En proceso',
        'En proceso' => 'Resuelto',
        'Resuelto' => 'Cerrado',
    ];

    public function update(Request $request, Ticket $ticket): RedirectResponse
    {
        $datos = $request->validate([
            'estado_id' => [
                'required',
                'integer',
                'exists:estados_ticket,id',
            ],
        ], [
            'estado_id.required' => 'No se recibio el siguiente estado.',
            'estado_id.exists' => 'El estado seleccionado no existe.',
        ]);

        $estadoNuevoNombre = DB::transaction(function () use ($request, $ticket, $datos): string {
            $ticketActual = Ticket::query()
                ->whereKey($ticket->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            // Un tecnico solo puede actualizar los tickets que tiene asignados.
            abort_unless(
                (int) $ticketActual->tecnico_id === (int) $request->user()->id,
                403,
                'No puedes actualizar un ticket que no tienes asignado.'
            );

            $estadoActual = EstadoTicket::query()->find($ticketActual->estado_id);

            if (! $estadoActual) {
                throw ValidationException::withMessages([
                    'estado_id' => 'El ticket no tiene un estado valido configurado.',
                ]);
            }

            $nombreSiguiente = self::TRANSICIONES[$estadoActual->nombre] ?? null;

            if ($nombreSiguiente === null) {
                throw ValidationException::withMessages([
                    'estado_id' => 'El ticket no tiene una transicion posterior permitida.',
                ]);
            }

            $estadoNuevo = EstadoTicket::query()
                ->where('nombre', $nombreSiguiente)
                ->first();

            // No se acepta un estado enviado manualmente que salte o revierta etapas.
            if (! $estadoNuevo || (int) $estadoNuevo->id !== (int) $datos['estado_id']) {
                throw ValidationException::withMessages([
                    'estado_id' => "La siguiente transicion permitida es: {$nombreSiguiente}.",
                ]);
            }

            $fechaCambio = now();
            $actualizaciones = [
                'estado_id' => $estadoNuevo->id,
            ];

            if ($nombreSiguiente === 'Resuelto') {
                $actualizaciones['resuelto_en'] = $fechaCambio;
            }

            if ($nombreSiguiente === 'Cerrado') {
                $actualizaciones['cerrado_en'] = $fechaCambio;
            }

            $ticketActual->forceFill($actualizaciones)->save();

            CambioEstado::create([
                'ticket_id' => $ticketActual->id,
                'usuario_id' => $request->user()->id,
                'estado_anterior_id' => $estadoActual->id,
                'estado_nuevo_id' => $estadoNuevo->id,
                'fecha' => $fechaCambio,
            ]);

            return $estadoNuevo->nombre;
        }, 3);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with(
                'status',
                "El ticket {$ticket->folio} actualizo su estado a {$estadoNuevoNombre}."
            );
    }
}
