<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Area extends Model
{
    use HasFactory;

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'activo',
    ];

    public function almacenes(): HasMany
    {
        return $this->hasMany(Almacen::class, 'area_id');
    }

    public function almacen(): HasOne
    {
        return $this->hasOne(Almacen::class, 'area_id')->oldestOfMany();
    }

    public function horarios()
    {
        return $this->hasMany(AreaHorario::class);
    }

    public function horarioActivo()
    {
        return $this->hasOne(AreaHorario::class)->where('activo', true)->latestOfMany();
    }
}

