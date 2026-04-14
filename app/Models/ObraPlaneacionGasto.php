<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ObraPlaneacionGasto extends Model
{
    use HasFactory;

    protected $table = 'obra_planeacion_gastos';

   protected $fillable = [
    'obra_id',
    'presupuesto_id',
    'partida',           // Nuevo
    'concepto',          // Nuevo
    'unidad',            // Nuevo
    'cantidad',          // Nuevo
    'precio_unitario',   // Nuevo
    'numero_semana',     
    'monto_programado',  // Aquí guardaremos el TOTAL de la fila del Excel
    'presupuesto_detalle_id',
    'presupuesto_pila_id',
];

    /**
     * Relación con la Obra
     */
    public function obra()
    {
        return $this->belongsTo(Obra::class);
    }

    /**
     * Relación con el detalle del presupuesto (Conceptos Generales)
     */
    public function presupuestoDetalle()
    {
        return $this->belongsTo(PresupuestoDetalle::class, 'presupuesto_detalle_id');
    }

    /**
     * Relación con las pilas (Conceptos de Perforación)
     */
    public function presupuestoPila()
    {
        return $this->belongsTo(PresupuestoPila::class, 'presupuesto_pila_id');
    }

    /**
     * Scope para filtrar por semana rápidamente
     */
    public function scopePorSemana($query, $semana)
    {
        return $query->where('numero_semana', $semana);
    }

    public function semanas()
{
    return $this->hasMany(ObraPlaneacionSemanal::class, 'planeacion_gasto_id');
}
}