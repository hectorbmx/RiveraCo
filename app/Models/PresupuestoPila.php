<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PresupuestoPila extends Model
{
    use HasFactory;

    // Nombre de la tabla (opcional si sigue la convención, pero mejor ser explícitos)
    protected $table = 'presupuesto_pilas';

    protected $fillable = [
        'presupuesto_id',
        'concepto',
        'unidad',
        'cantidad',
        'costo',
        'total',
        'optimista',
        'pesimista'
    ];

    /**
     * Relación: Una fila de pila pertenece a un presupuesto cabecera.
     */
    public function presupuesto()
    {
        return $this->belongsTo(Presupuesto::class);
    }
}