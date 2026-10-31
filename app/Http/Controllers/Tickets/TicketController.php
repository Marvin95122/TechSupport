<?php

namespace App\Http\Controllers\Tickets;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\Equipo;
use App\Models\EstadoTicket;
use App\Models\Prioridad;
use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TicketController extends Controller
{
    /** HU-09: formulario de registro para un solicitante autenticado. */
    public function create(): View
    {
        return view('tickets.create', [
            'categorias' => Categoria::query()->where('activo', true)->orderBy('nombre')->get(),
            'prioridades' => Prioridad::query()->orderBy('nivel')->get(),
            'equipos' => Equipo::query()->where('activo', true)->orderBy('codigo')->get(),
        ]);
    }

    /** El solicitante y el estado inicial siempre se fijan en el servidor. */
    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'asunto' => ['required', 'string', 'min:5', 'max:180'],
            'descripcion' => ['required', 'string', 'min:10', 'max:5000'],
            'categoria_id' => [
                'required', 'integer',
                Rule::exists('categorias', 'id')->where('activo', true),
            ],
            'prioridad_id' => ['required', 'integer', Rule::exists('prioridades', 'id')],
            'equipo_id' => [
                'nullable', 'integer',
                Rule::exists('equipos', 'id')->where('activo', true),
            ],
        ], [
            'categoria_id.exists' => 'Selecciona una categoría disponible.',
            'prioridad_id.exists' => 'Selecciona una prioridad válida.',
            'equipo_id.exists' => 'Selecciona un equipo activo o deja el campo vacío.',
        ]);

        $pendiente = EstadoTicket::query()->where('nombre', 'Pendiente')->first();
        if (! $pendiente) {
            return back()->withInput()->withErrors([
                'categoria_id' => 'Falta el catálogo de estados. Contacta al administrador.',
            ]);
        }

        // ULID: identificador único que no depende de consultar el último registro.
        // La base de datos también exige folio UNIQUE.
        $folio = 'TS-'.strtoupper((string) Str::ulid());

        Ticket::create([
            'folio' => $folio,
            'solicitante_id' => $request->user()->id,
            'equipo_id' => $datos['equipo_id'] ?? null,
            'categoria_id' => $datos['categoria_id'],
            'prioridad_id' => $datos['prioridad_id'],
            'estado_id' => $pendiente->id,
            'asunto' => trim($datos['asunto']),
            'descripcion' => trim($datos['descripcion']),
            // creado_en tiene DEFAULT CURRENT_TIMESTAMP en la base de datos.
        ]);

        return redirect()->route('tickets.create')
            ->with('status', "Solicitud {$folio} registrada correctamente. Estado: Pendiente.");
    }
}
