<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MantenimientoFoto extends Model
{
    protected $table = 'mantenimiento_fotos';

    protected $fillable = [
        'mantenimiento_id',
        'ruta',
        'descripcion',
    ];

    public function mantenimiento()
    {
        return $this->belongsTo(Mantenimiento::class, 'mantenimiento_id');
    }
}
