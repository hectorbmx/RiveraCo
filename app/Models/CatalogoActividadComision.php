<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatalogoActividadComision extends Model
{
    protected $table = 'catalogo_actividades_comision';

    protected $fillable = [
        'key',
        'nombre',
        'uom_id',
        'orden',
        'activa',
    ];

    protected $casts = [
        'activa' => 'boolean',
        'orden'  => 'integer',
    ];

    public function uom()
    {
        return $this->belongsTo(Uom::class, 'uom_id');
    }
}
