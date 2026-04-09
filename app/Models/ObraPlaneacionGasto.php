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
        'presupuesto_detalle_id',
        'presupuesto_pila_id',
        'numero_semana',
        'monto_programado',
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
}