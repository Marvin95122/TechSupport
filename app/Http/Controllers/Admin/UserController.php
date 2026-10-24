<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    private const ROLES = [
        'administrador',
        'tecnico',
        'solicitante',
    ];

    public function index(): View
    {
        $users = User::query()
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'email',
                'role',
                'is_active',
            ]);

        return view('admin.users.index', compact('users'));
    }

    public function create(): View
    {
        return view('admin.users.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
            ],
            'role' => ['required', Rule::in(self::ROLES)],
            'password' => [
                'required',
                'string',
                'min:12',
                'confirmed',
            ],
        ]);

        $user = new User();
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->password = $validated['password'];
        $user->role = $validated['role'];
        $user->is_active = true;
        $user->save();

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'Usuario registrado correctamente.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(
        Request $request,
        User $user
    ): RedirectResponse {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'role' => ['required', Rule::in(self::ROLES)],
            'password' => [
                'nullable',
                'string',
                'min:12',
                'confirmed',
            ],
        ]);

        if (
            $user->id === $request->user()->id
            && $validated['role'] !== 'administrador'
        ) {
            return back()
                ->withErrors([
                    'role' => 'No puedes quitarte tu propio rol de administrador.',
                ])
                ->withInput();
        }

        if (
            $user->role === 'administrador'
            && $user->is_active
            && $validated['role'] !== 'administrador'
        ) {
            $anotherAdminExists = User::query()
                ->where('role', 'administrador')
                ->where('is_active', true)
                ->where('id', '!=', $user->id)
                ->exists();

            if (! $anotherAdminExists) {
                return back()
                    ->withErrors([
                        'role' => 'No puedes quitar el rol al último administrador activo.',
                    ])
                    ->withInput();
            }
        }

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->role = $validated['role'];

        if (! empty($validated['password'])) {
            $user->password = $validated['password'];
        }

        $user->save();

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'Usuario actualizado correctamente.');
    }

    public function toggleStatus(
        Request $request,
        User $user
    ): RedirectResponse {
        if (
            $user->id === $request->user()->id
            && $user->is_active
        ) {
            return back()->withErrors([
                'account' => 'No puedes desactivar tu propia cuenta.',
            ]);
        }

        if (
            $user->role === 'administrador'
            && $user->is_active
        ) {
            $anotherAdminExists = User::query()
                ->where('role', 'administrador')
                ->where('is_active', true)
                ->where('id', '!=', $user->id)
                ->exists();

            if (! $anotherAdminExists) {
                return back()->withErrors([
                    'account' => 'No puedes desactivar al último administrador activo.',
                ]);
            }
        }

        $user->is_active = ! $user->is_active;
        $user->save();

        $message = $user->is_active
            ? 'Cuenta activada correctamente.'
            : 'Cuenta desactivada correctamente.';

        return redirect()
            ->route('admin.users.index')
            ->with('status', $message);
    }
}