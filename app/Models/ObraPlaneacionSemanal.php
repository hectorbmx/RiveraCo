<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ObraPlaneacionSemanal extends Model
{
    use HasFactory;

    protected $table = 'obra_planeacion_semanal';

    protected $fillable = [
        'planeacion_gasto_id',
        'numero_semana',
        'monto_programado',
    ];

    public function gastoBase()
    {
        return $this->belongsTo(ObraPlaneacionGasto::class, 'planeacion_gasto_id');
    }
}