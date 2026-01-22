<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComisionPerforacion extends Model
{
    use HasFactory;

    protected $table = 'comision_perforaciones';

    protected $fillable = [
        'comision_id',
        'hora_inicio',
        'hora_termino',
        'informacion_pila',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function comision()
    {
        return $this->belongsTo(Comision::class, 'comision_id');
    }
}
