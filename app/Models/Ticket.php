<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table(name: 'tickets', timestamps: false)]
#[Fillable([
    'folio',
    'solicitante_id',
    'equipo_id',
    'categoria_id',
    'prioridad_id',
    'estado_id',
    'asunto',
    'descripcion',
])]
class Ticket extends Model
{
    protected function casts(): array
    {
        return [
            'creado_en' => 'datetime',
            'asignado_en' => 'datetime',
            'resuelto_en' => 'datetime',
            'cerrado_en' => 'datetime',
        ];
    }

    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitante_id');
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class, 'equipo_id');
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function prioridad(): BelongsTo
    {
        return $this->belongsTo(Prioridad::class, 'prioridad_id');
    }

    public function estado(): BelongsTo
    {
        return $this->belongsTo(EstadoTicket::class, 'estado_id');
    }

    public function tecnico(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tecnico_id');
    }

    public function asignadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asignado_por_id');
    }
}
