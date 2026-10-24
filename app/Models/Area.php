<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table(name: 'areas', timestamps: false)]
#[Fillable(['nombre'])]
class Area extends Model
{
    use HasFactory;

    public function equipos(): HasMany
    {
        return $this->hasMany(Equipo::class, 'area_id');
    }
}
