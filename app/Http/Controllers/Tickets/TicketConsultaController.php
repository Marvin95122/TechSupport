<?php

namespace App\Http\Controllers\Tickets;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\EstadoTicket;
use App\Models\Prioridad;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketTimelineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Sprint 4 HU-10/HU-12, Sprint 6 HU-19 y Sprint 7 HU-21/HU-22. */
class TicketConsultaController extends Controller
{
    public function index(Request $request): View
    {
        $usuario = $request->user();
        $esAdministrador = $usuario->role === 'administrador';
        $esTecnico = $usuario->role === 'tecnico';
        $esSolicitante = $usuario->role === 'solicitante';
        abort_unless($esAdministrador || $esTecnico || $esSolicitante, 403);

        $validador = Validator::make($request->query(), [
            'buscar' => ['nullable', 'string', 'max:120'],
            'categoria' => ['nullable', 'integer', Rule::exists('categorias', 'id')],
            'prioridad' => ['nullable', 'integer', Rule::exists('prioridades', 'id')],
            'estado' => ['nullable', 'integer', Rule::exists('estados_ticket', 'id')],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d'],
        ]);

        // Solo se comparan fechas cuando ambas fueron proporcionadas; permite filtrar únicamente "Hasta".
        $validador->after(function ($validador) use ($request): void {
            $desde = $request->query('desde');
            $hasta = $request->query('hasta');

            if (
                is_string($desde) && $desde !== '' &&
                is_string($hasta) && $hasta !== '' &&
                $hasta < $desde
            ) {
                $validador->errors()->add(
                    'hasta',
                    'La fecha final debe ser igual o posterior a la fecha inicial.'
                );
            }
        });

        $filtros = $validador->validate();

        $buscar = trim((string) ($filtros['buscar'] ?? ''));
        $categoria = (string) ($filtros['categoria'] ?? '');
        $prioridad = (string) ($filtros['prioridad'] ?? '');
        $estado = (string) ($filtros['estado'] ?? '');
        $desde = (string) ($filtros['desde'] ?? '');
        $hasta = (string) ($filtros['hasta'] ?? '');

        $consulta = Ticket::query()
            ->with(['solicitante', 'tecnico', 'categoria', 'prioridad', 'estado', 'equipo']);

        // El alcance se aplica antes de los filtros para evitar revelar tickets ajenos.
        if ($esTecnico) {
            $consulta->where('tecnico_id', $usuario->id);
        } elseif ($esSolicitante) {
            $consulta->where('solicitante_id', $usuario->id);
        }

        if ($buscar !== '') {
            $termino = '%'.$buscar.'%';
            $consulta->where(function ($query) use ($termino, $esAdministrador): void {
                $query->where('folio', 'like', $termino)
                    ->orWhere('asunto', 'like', $termino)
                    ->orWhereHas('solicitante', function ($relacion) use ($termino): void {
                        $relacion->where('name', 'like', $termino)
                            ->orWhere('email', 'like', $termino);
                    })
                    ->orWhereHas('equipo', function ($relacion) use ($termino): void {
                        $relacion->where('codigo', 'like', $termino)
                            ->orWhere('tipo', 'like', $termino)
                            ->orWhere('marca', 'like', $termino)
                            ->orWhere('modelo', 'like', $termino)
                            ->orWhere('serie', 'like', $termino);
                    });

                if ($esAdministrador) {
                    $query->orWhereHas('tecnico', function ($relacion) use ($termino): void {
                        $relacion->where('name', 'like', $termino)
                            ->orWhere('email', 'like', $termino);
                    });
                }
            });
        }

        $consulta
            ->when($categoria !== '', fn ($query) => $query->where('categoria_id', (int) $categoria))
            ->when($prioridad !== '', fn ($query) => $query->where('prioridad_id', (int) $prioridad))
            ->when($estado !== '', fn ($query) => $query->where('estado_id', (int) $estado))
            ->when($desde !== '', fn ($query) => $query->whereDate('creado_en', '>=', $desde))
            ->when($hasta !== '', fn ($query) => $query->whereDate('creado_en', '<=', $hasta));

        $tickets = $consulta
            ->orderByDesc('creado_en')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $categorias = Categoria::query()->orderBy('nombre')->get(['id', 'nombre']);
        $prioridades = Prioridad::query()->orderBy('nivel')->get(['id', 'nombre']);
        $estados = EstadoTicket::query()->orderBy('orden')->get(['id', 'nombre']);

        return view('tickets.index', compact(
            'tickets',
            'categorias',
            'prioridades',
            'estados',
            'buscar',
            'categoria',
            'prioridad',
            'estado',
            'desde',
            'hasta',
            'esAdministrador',
            'esTecnico',
            'esSolicitante'
        ));
    }

    public function show(
        Request $request,
        Ticket $ticket,
        TicketTimelineService $timelineService
    ): View {
        $usuario = $request->user();
        $esAdministrador = $usuario->role === 'administrador';
        $esTecnico = $usuario->role === 'tecnico';

        abort_unless(
            $esAdministrador ||
            ($usuario->role === 'solicitante' && (int) $ticket->solicitante_id === (int) $usuario->id) ||
            ($esTecnico && (int) $ticket->tecnico_id === (int) $usuario->id),
            403,
            'No tienes permisos para consultar esta solicitud.'
        );

        $ticket->load([
            'solicitante',
            'categoria',
            'prioridad',
            'estado',
            'equipo',
            'tecnico',
            'asignadoPor',
            'cambiosEstado.usuario',
            'cambiosEstado.estadoAnterior',
            'cambiosEstado.estadoNuevo',
            'observaciones.usuario',
            'soluciones.usuario',
        ]);

        // No se descarga el catálogo completo: el selector consulta el servidor bajo demanda.
        $tecnicoSeleccionado = null;
        $tecnicoAnterior = old('tecnico_id');
        if ($esAdministrador && is_scalar($tecnicoAnterior) && ctype_digit((string) $tecnicoAnterior)) {
            $tecnicoSeleccionado = User::query()
                ->whereKey((int) $tecnicoAnterior)
                ->where('role', 'tecnico')
                ->where('is_active', true)
                ->first(['id', 'name', 'email']);
        }

        $siguienteEstadoNombre = match ($ticket->estado?->nombre) {
            'Asignado' => 'En proceso',
            'En proceso' => 'Resuelto',
            'Resuelto' => 'Cerrado',
            default => null,
        };

        $siguienteEstado = $esTecnico && $siguienteEstadoNombre !== null
            ? EstadoTicket::query()->where('nombre', $siguienteEstadoNombre)->first()
            : null;

        $historial = $timelineService->build($ticket);

        return view('tickets.show', compact(
            'ticket',
            'esAdministrador',
            'esTecnico',
            'tecnicoSeleccionado',
            'siguienteEstado',
            'historial'
        ));
    }
}
