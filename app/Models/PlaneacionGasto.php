<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlaneacionGasto extends Model
{
    use HasFactory;

    // Nombre exacto de la tabla que creaste en la migración
    protected $table = 'planeacion_gastos';

    /**
     * fillable: Permite que estos campos se llenen mediante 
     * el método create() en tu controlador.
     */
    protected $fillable = [
        'obra_id',
        'partida',
        'concepto',
        'unidad',
        'cantidad',
        'precio_unitario',
        'total'
    ];

    /**
     * Relación inversa: Cada registro de gasto pertenece a una obra.
     */
    public function obra()
    {
        return $this->belongsTo(Obra::class, 'obra_id');
    }
    public function distribucionSemanas()
        {
            return $this->hasMany(PlaneacionSemanal::class, 'planeacion_gasto_id');
        }
}