<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PresupuestoDetalle extends Model
{
    protected $table = 'presupuesto_detalles';

    protected $fillable = [
        'presupuesto_id',
        'partida',
        'concepto',
        'unidad',
        'cantidad',
        'precio_unitario',
        'importe',
        'importe_optimista',
        'importe_pesimista',
    ];

    // Relación inversa: Un detalle pertenece a un presupuesto
    public function presupuesto()
    {
        return $this->belongsTo(Presupuesto::class);
    }
}