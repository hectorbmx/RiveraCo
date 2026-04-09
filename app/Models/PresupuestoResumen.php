<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PresupuestoResumen extends Model
{
    use HasFactory;

    // Indicamos la tabla explícitamente por el plural en español
    protected $table = 'presupuesto_resumenes';

    protected $fillable = [
        'presupuesto_id',
        'partida',
        'concepto',
        'unidad',
        'cantidad',
        'precio_unitario',
        'importe'
    ];

    /**
     * Relación inversa: Un resumen pertenece a un presupuesto
     */
    public function presupuesto()
    {
        return $this->belongsTo(Presupuesto::class);
    }
}