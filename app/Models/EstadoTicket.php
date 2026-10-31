<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table(name: 'estados_ticket', timestamps: false)]
#[Fillable(['nombre', 'orden'])]
class EstadoTicket extends Model
{
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'estado_id');
    }
}
