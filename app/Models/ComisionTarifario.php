<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComisionTarifario extends Model
{
    protected $table = 'comision_tarifarios';

    protected $fillable = [
        'nombre',
        'descripcion',
        'estado',
        'vigente_desde',
        'vigente_hasta',
        'created_by',
        'published_by',
        'published_at',
    ];

    protected $casts = [
        'vigente_desde' => 'datetime',
        'vigente_hasta' => 'datetime',
        'published_at'  => 'datetime',
    ];

    public function detalles()
    {
        return $this->hasMany(ComisionTarifarioDetalle::class, 'comision_tarifario_id');
    }
}
