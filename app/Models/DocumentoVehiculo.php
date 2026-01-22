<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentoVehiculo extends Model
{
    protected $table = 'documentos_vehiculo';

    protected $fillable = [
        'vehiculo_id',
        'tipo',
        'numero',
        'fecha_emision',
        'fecha_vencimiento',
        'archivo',
        'notas',
    ];

    protected $casts = [
        'fecha_emision'     => 'date',
        'fecha_vencimiento' => 'date',
    ];

    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class, 'vehiculo_id');
    }
}
