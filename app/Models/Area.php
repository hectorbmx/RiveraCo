<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    use HasFactory;

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'activo',
    ];

    public function horarios()
    {
        return $this->hasMany(AreaHorario::class);
    }

    public function horarioActivo()
    {
        return $this->hasOne(AreaHorario::class)->where('activo', true)->latestOfMany();
    }
}
