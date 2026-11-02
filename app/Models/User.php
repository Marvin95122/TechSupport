<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function cambiosEstado(): HasMany
    {
        return $this->hasMany(CambioEstado::class, 'usuario_id');
    }

    /** Tickets cuyo técnico responsable es este usuario. */
    public function ticketsAsignados(): HasMany
    {
        return $this->hasMany(Ticket::class, 'tecnico_id');
    }

    /** Tickets que este usuario asignó como administrador. */
    public function ticketsAsignadosPor(): HasMany
    {
        return $this->hasMany(Ticket::class, 'asignado_por_id');
    }
}
