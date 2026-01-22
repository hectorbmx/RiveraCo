<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ObraContrato extends Model
{
    use HasFactory;

    protected $fillable = [
        'obra_id',
        'tipo',
        'nombre',
        'descripcion',
        'monto_contrato',
        'fecha_firma',
        'archivo_path',
    ];

    protected $casts = [
        'fecha_firma' => 'date',
        'monto_contrato' => 'decimal:2',
    ];

    public function obra()
    {
        return $this->belongsTo(Obra::class);
    }
}
