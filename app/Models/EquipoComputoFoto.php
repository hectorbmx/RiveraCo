<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EquipoComputoFoto extends Model
{
    protected $table = 'equipo_computo_fotos';

    protected $fillable = [
        'equipo_computo_id',
        'equipo_computo_movimiento_id',
        'path',
        'original_name',
        'uploaded_by',
    ];

    public function equipo()
    {
        return $this->belongsTo(EquipoComputo::class, 'equipo_computo_id');
    }

    public function movimiento()
    {
        return $this->belongsTo(EquipoComputoMovimiento::class, 'equipo_computo_movimiento_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
