<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ObraPresupuesto extends Model
{
    protected $fillable = [
        'obra_id',
        'nombre',
        'version',
        'fecha',
        'notas',
        'archivo_path',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    public function obra()
    {
        return $this->belongsTo(Obra::class);
    }
}
