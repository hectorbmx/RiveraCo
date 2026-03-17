<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehiculoDocumento extends Model
{
    protected $table = 'vehiculo_documentos';

    protected $fillable = [
        'vehiculo_id',
        'tipo',
        'nombre_original',
        'archivo_path',
        'mime_type',
        'tamano',
        'fecha_documento',
        'fecha_vencimiento',
        'vigente',
        'observaciones',
    ];

    protected $casts = [
        'fecha_documento' => 'date',
        'fecha_vencimiento' => 'date',
        'vigente' => 'boolean',
    ];

    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class);
    }
}