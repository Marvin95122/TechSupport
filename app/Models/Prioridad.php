<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table(name: 'prioridades', timestamps: false)]
#[Fillable(['nivel', 'nombre'])]
class Prioridad extends Model
{
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'prioridad_id');
    }
}
