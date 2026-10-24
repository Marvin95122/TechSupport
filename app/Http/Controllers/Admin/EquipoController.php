<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Equipo;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class EquipoController extends Controller
{
    public function index(Request $request): View
    {
        $buscar = trim((string) $request->query('buscar', ''));

        $equipos = Equipo::query()
            ->with(['area', 'responsable'])
            ->where('activo', true)
            ->when($buscar !== '', function ($query) use ($buscar): void {
                $query->where(function ($q) use ($buscar): void {
                    $q->where('codigo', 'like', '%'.$buscar.'%')
                        ->orWhere('tipo', 'like', '%'.$buscar.'%')
                        ->orWhere('marca', 'like', '%'.$buscar.'%')
                        ->orWhere('modelo', 'like', '%'.$buscar.'%');
                });
            })
            ->orderBy('codigo')
            ->paginate(10)
            ->withQueryString();

        return view('admin.equipos.index', compact('equipos', 'buscar'));
    }

    public function create(): View
    {
        $areas = Area::query()->orderBy('nombre')->get();
        $responsables = User::query()->where('is_active', true)->orderBy('name')->get();

        return view('admin.equipos.create', compact('areas', 'responsables'));
    }

    public function store(Request $request): RedirectResponse
    {
        // Normalizar antes de validar la unicidad (sin alterar valores que no sean cadenas).
        foreach (['codigo', 'tipo', 'marca', 'modelo', 'serie'] as $campo) {
            if (is_string($request->input($campo))) {
                $request->merge([$campo => trim($request->input($campo))]);
            }
        }

        $validated = $request->validate([
            'codigo' => ['required', 'string', 'max:50', Rule::unique('equipos', 'codigo')],
            'area_id' => ['nullable', 'integer', Rule::exists('areas', 'id')],
            'responsable_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('is_active', true)],
            'tipo' => ['required', 'string', 'max:100'],
            'marca' => ['required', 'string', 'max:100'],
            'modelo' => ['required', 'string', 'max:100'],
            'serie' => ['nullable', 'string', 'max:100', Rule::unique('equipos', 'serie')],
            'estado' => ['required', Rule::in(['operativo', 'mantenimiento', 'fuera_de_servicio'])],
        ], [
            'codigo.unique' => 'Este código de equipo ya está registrado.',
            'serie.unique' => 'Este número de serie ya está registrado.',
            'responsable_id.exists' => 'Selecciona una cuenta activa y válida.',
        ]);

        $validated['codigo'] = trim($validated['codigo']);
        $validated['serie'] = isset($validated['serie']) && trim($validated['serie']) !== ''
            ? trim($validated['serie'])
            : null;

        $equipo = Equipo::create($validated);

        return redirect()->route('admin.equipos.show', $equipo)
            ->with('status', 'Equipo registrado correctamente.');
    }

    public function show(Equipo $equipo): View
    {
        $equipo->load(['area', 'responsable']);

        return view('admin.equipos.show', compact('equipo'));
    }

    public function storeArea(Request $request): RedirectResponse
    {
        if (is_string($request->input('nombre'))) {
            $request->merge(['nombre' => trim($request->input('nombre'))]);
        }

        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:150', Rule::unique('areas', 'nombre')],
        ], ['nombre.unique' => 'Ya existe un área con ese nombre.']);

        Area::create(['nombre' => trim($validated['nombre'])]);

        return redirect()->route('admin.equipos.create')
            ->with('status', 'Área registrada correctamente. Ya puedes seleccionarla.');
    }

    /**
     * Mostrar la edición de un equipo activo, sin alterar su identificador.
     */
    public function edit(Equipo $equipo): View
    {
        abort_unless($equipo->activo, 404);

        $areas = Area::query()->orderBy('nombre')->get();
        $responsables = User::query()->where('is_active', true)->orderBy('name')->get();

        return view('admin.equipos.edit', compact('equipo', 'areas', 'responsables'));
    }

    /**
     * Actualizar solamente los campos editables. La baja lógica tiene su propia ruta.
     */
    public function update(Request $request, Equipo $equipo): RedirectResponse
    {
        abort_unless($equipo->activo, 404);

        foreach (['codigo', 'tipo', 'marca', 'modelo', 'serie'] as $campo) {
            if (is_string($request->input($campo))) {
                $request->merge([$campo => trim($request->input($campo))]);
            }
        }

        $validated = $request->validate([
            'codigo' => ['required', 'string', 'max:50', Rule::unique('equipos', 'codigo')->ignore($equipo->id)],
            'area_id' => ['nullable', 'integer', Rule::exists('areas', 'id')],
            'responsable_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('is_active', true)],
            'tipo' => ['required', 'string', 'max:100'],
            'marca' => ['required', 'string', 'max:100'],
            'modelo' => ['required', 'string', 'max:100'],
            'serie' => ['nullable', 'string', 'max:100', Rule::unique('equipos', 'serie')->ignore($equipo->id)],
            'estado' => ['required', Rule::in(['operativo', 'mantenimiento', 'fuera_de_servicio'])],
        ], [
            'codigo.unique' => 'Este código de equipo ya está registrado.',
            'serie.unique' => 'Este número de serie ya está registrado.',
            'responsable_id.exists' => 'Selecciona una cuenta activa y válida.',
        ]);

        $validated['serie'] = isset($validated['serie']) && $validated['serie'] !== ''
            ? $validated['serie']
            : null;

        // No aceptar activo, id ni otras columnas de la petición.
        $equipo->update($validated);

        return redirect()->route('admin.equipos.show', $equipo)
            ->with('status', 'Equipo actualizado correctamente.');
    }

    /**
     * Baja lógica. No borra el equipo ni sus futuras referencias en tickets.
     */
    public function deactivate(Equipo $equipo): RedirectResponse
    {
        if (! $equipo->activo) {
            return redirect()->route('admin.equipos.show', $equipo)
                ->with('status', 'El equipo ya se encontraba desactivado.');
        }

        $equipo->update(['activo' => false]);

        return redirect()->route('admin.equipos.show', $equipo)
            ->with('status', 'Equipo desactivado correctamente.');
    }
}
