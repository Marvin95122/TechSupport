<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CambioEstado;
use App\Models\EstadoTicket;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TicketAsignacionController extends Controller
{
    /**
     * HU-13: asigna un ticket pendiente a un técnico activo.
     *
     * La transacción conserva juntos la asignación y su cambio de estado.
     */
    public function store(Request $request, Ticket $ticket): RedirectResponse
    {
        $datos = $request->validate([
            'tecnico_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')
                    ->where('role', 'tecnico')
                    ->where('is_active', true),
            ],
        ], [
            'tecnico_id.required' => 'Selecciona un técnico.',
            'tecnico_id.exists' => 'El técnico seleccionado no está activo o no tiene el rol autorizado.',
        ]);

        $nombreTecnico = DB::transaction(function () use ($request, $ticket, $datos): string {
            // Se bloquea el ticket para impedir dos asignaciones simultáneas.
            $ticketActual = Ticket::query()
                ->whereKey($ticket->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $pendiente = EstadoTicket::query()
                ->where('nombre', 'Pendiente')
                ->first();

            $asignado = EstadoTicket::query()
                ->where('nombre', 'Asignado')
                ->first();

            if (! $pendiente || ! $asignado) {
                throw ValidationException::withMessages([
                    'tecnico_id' => 'No están configurados los estados necesarios. Verifica el catálogo de estados.',
                ]);
            }

            // HU-13 no contempla reasignaciones: evita sobrescribir al responsable.
            if (
                (int) $ticketActual->estado_id !== (int) $pendiente->id ||
                $ticketActual->tecnico_id !== null
            ) {
                throw ValidationException::withMessages([
                    'tecnico_id' => 'Solo se pueden asignar tickets pendientes que todavía no tengan un técnico responsable.',
                ]);
            }

            // Se vuelve a comprobar dentro de la transacción por si la cuenta cambió.
            $tecnico = User::query()
                ->whereKey($datos['tecnico_id'])
                ->where('role', 'tecnico')
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if (! $tecnico) {
                throw ValidationException::withMessages([
                    'tecnico_id' => 'El técnico seleccionado dejó de estar activo o ya no está autorizado.',
                ]);
            }

            $fechaAsignacion = now();

            $ticketActual->forceFill([
                'tecnico_id' => $tecnico->id,
                'asignado_por_id' => $request->user()->id,
                'asignado_en' => $fechaAsignacion,
                'estado_id' => $asignado->id,
            ])->save();

            CambioEstado::create([
                'ticket_id' => $ticketActual->id,
                'usuario_id' => $request->user()->id,
                'estado_anterior_id' => $pendiente->id,
                'estado_nuevo_id' => $asignado->id,
                'fecha' => $fechaAsignacion,
            ]);

            return $tecnico->name;
        }, 3);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with(
                'status',
                "Ticket {$ticket->folio} asignado correctamente a {$nombreTecnico}. Estado actualizado a Asignado."
            );
    }
}
