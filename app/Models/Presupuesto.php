<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Presupuesto extends Model
{
    use HasFactory;

    // Definimos el nombre de la tabla (opcional si sigue la convención plural)
    protected $table = 'presupuestos';

    // Campos que permitimos llenar desde el Request de Excel
    protected $fillable = [
        'codigo_proyecto',
        'nombre_cliente',
        'descripcion',
        'total_costo_directo',
        'total_presupuesto',
        'estatus',
    ];

    /**
     * Opcional: Si quieres que los montos siempre se traten como números
     * con decimales al recuperarlos en Laravel.
     */
    protected $casts = [
        'total_costo_directo' => 'decimal:2',
        'total_presupuesto'   => 'decimal:2',
    ];

    public function detalles()
        {
            return $this->hasMany(PresupuestoDetalle::class);
        }

        public function pilas()
        {
            // Cambiamos PresupuestoPilas por PresupuestoPila
            return $this->hasMany(PresupuestoPila::class);
        }
        public function resumenes()
        {
            return $this->hasMany(PresupuestoResumen::class);
        }
        public function obras()
        {
            return $this->belongsToMany(Obra::class, 'obra_presupuesto');
        }
}