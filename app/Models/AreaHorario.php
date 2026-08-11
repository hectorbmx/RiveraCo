<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AreaHorario extends Model
{
    use HasFactory;

    protected $fillable = [
        'area_id',
        'nombre',
        'hora_entrada',
        'hora_salida',
        'dias_laborables',
        'minutos_comida',
        'minutos_tolerancia',
        'activo',
    ];

    protected $casts = [
        'dias_laborables' => 'array',
        'minutos_comida' => 'integer',
        'minutos_tolerancia' => 'integer',
        'activo' => 'boolean',
    ];

    public function area()
    {
        return $this->belongsTo(Area::class);
    }
}
