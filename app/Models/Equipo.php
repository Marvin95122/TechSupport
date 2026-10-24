<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table(name: 'equipos', timestamps: false)]
#[Fillable([
    'codigo',
    'area_id',
    'responsable_id',
    'tipo',
    'marca',
    'modelo',
    'serie',
    'estado',
    'activo',
])]
class Equipo extends Model
{
    use HasFactory;

    protected $attributes = [
        'activo' => 1,
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }
}
