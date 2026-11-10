<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Services\TicketTimelineService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Vista de solo lectura del expediente incluido en el reporte administrativo. */
class ReporteAtencionDetalleController extends Controller
{
    public function show(
        Request $request,
        Ticket $ticket,
        TicketTimelineService $timelineService
    ): View {
        $ticket->load([
            'solicitante:id,name,email',
            'tecnico:id,name,email',
            'equipo',
            'categoria',
            'prioridad',
            'estado',
            'asignadoPor:id,name',
            'cambiosEstado.usuario:id,name',
            'cambiosEstado.estadoAnterior:id,nombre',
            'cambiosEstado.estadoNuevo:id,nombre',
            'observaciones.usuario:id,name',
            'soluciones.usuario:id,name',
        ]);

        $historial = $timelineService->build($ticket);
        $filtros = $request->only([
            'fecha_inicio',
            'fecha_fin',
            'estado_id',
            'categoria_id',
            'prioridad_id',
            'tecnico_id',
        ]);

        return view('admin.reportes.atencion-detalle', compact(
            'ticket',
            'historial',
            'filtros'
        ));
    }
}
