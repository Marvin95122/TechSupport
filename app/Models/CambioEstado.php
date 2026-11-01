<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table(name: 'cambios_estado', timestamps: false)]
#[Fillable([
    'ticket_id',
    'usuario_id',
    'estado_anterior_id',
    'estado_nuevo_id',
    'fecha',
])]
class CambioEstado extends Model
{
    protected function casts(): array
    {
        return [
            'fecha' => 'datetime',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function estadoAnterior(): BelongsTo
    {
        return $this->belongsTo(
            EstadoTicket::class,
            'estado_anterior_id'
        );
    }

    public function estadoNuevo(): BelongsTo
    {
        return $this->belongsTo(
            EstadoTicket::class,
            'estado_nuevo_id'
        );
    }
}
