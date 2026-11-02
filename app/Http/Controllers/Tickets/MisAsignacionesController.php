<?php

namespace App\Http\Controllers\Tickets;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MisAsignacionesController extends Controller
{
    public function index(Request $request): View
    {
        $tickets = Ticket::query()
            ->with(['prioridad', 'estado'])
            ->where('tecnico_id', $request->user()->id)
            ->orderByDesc('creado_en')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('tickets.asignaciones', compact('tickets'));
    }
}
