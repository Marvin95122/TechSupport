<?php

namespace App\Services;

use App\Models\Ticket;
use Illuminate\Support\Collection;

class TicketTimelineService
{
    /**
     * Construye una cronología única a partir del registro, los cambios de estado,
     * las observaciones, las actividades y los diagnósticos/soluciones.
     *
     * Los registros originales no se modifican: solamente se proyectan para lectura.
     */
    public function build(Ticket $ticket): Collection
    {
        $eventos = collect();

        $eventos->push([
            'tipo' => 'registro',
            'titulo' => 'Solicitud registrada',
            'descripcion' => 'Se registró la solicitud de soporte.',
            'autor' => $ticket->solicitante?->name ?? 'Usuario solicitante',
            'fecha' => $ticket->creado_en ?? now(),
            'orden' => 0,
            'id' => 0,
        ]);

        foreach ($ticket->cambiosEstado as $cambio) {
            $estadoAnterior = $cambio->estadoAnterior?->nombre ?? 'Sin estado previo';
            $estadoNuevo = $cambio->estadoNuevo?->nombre ?? 'Sin estado nuevo';

            $eventos->push([
                'tipo' => 'estado',
                'titulo' => 'Cambio de estado',
                'descripcion' => "El estado cambió de {$estadoAnterior} a {$estadoNuevo}.",
                'autor' => $cambio->usuario?->name ?? 'Usuario no disponible',
                'fecha' => $cambio->fecha,
                'orden' => 1,
                'id' => $cambio->id,
            ]);
        }

        foreach ($ticket->observaciones as $observacion) {
            $esActividad = $observacion->tipo === 'actividad';

            $eventos->push([
                'tipo' => $esActividad ? 'actividad' : 'observacion',
                'titulo' => $esActividad ? 'Actividad registrada' : 'Observación registrada',
                'descripcion' => $observacion->contenido,
                'autor' => $observacion->usuario?->name ?? 'Usuario no disponible',
                'fecha' => $observacion->fecha,
                'orden' => 2,
                'id' => $observacion->id,
            ]);
        }

        foreach ($ticket->soluciones as $solucion) {
            $descripcion = "Diagnóstico:\n{$solucion->diagnostico}"
                ."\n\nAcciones ejecutadas:\n{$solucion->acciones_realizadas}"
                ."\n\nSolución aplicada:\n{$solucion->solucion}";

            $eventos->push([
                'tipo' => 'solucion',
                'titulo' => 'Diagnóstico y solución registrados',
                'descripcion' => $descripcion,
                'autor' => $solucion->usuario?->name ?? 'Usuario no disponible',
                'fecha' => $solucion->fecha,
                'orden' => 3,
                'id' => $solucion->id,
            ]);
        }

        return $eventos
            ->sort(function (array $a, array $b): int {
                $comparacion = $a['fecha']->getTimestamp() <=> $b['fecha']->getTimestamp();

                if ($comparacion !== 0) {
                    return $comparacion;
                }

                $comparacion = $a['orden'] <=> $b['orden'];

                return $comparacion !== 0
                    ? $comparacion
                    : $a['id'] <=> $b['id'];
            })
            ->values();
    }
}
