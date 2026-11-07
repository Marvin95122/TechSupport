<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table(name: 'ticket_soluciones', timestamps: false)]
#[Fillable([
    'ticket_id',
    'usuario_id',
    'diagnostico',
    'acciones_realizadas',
    'solucion',
    'fecha',
])]
class TicketSolucion extends Model
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
}
