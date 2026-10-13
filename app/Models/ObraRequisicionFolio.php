<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ObraRequisicionFolio extends Model
{
    use HasFactory;

    protected $table = 'obra_requisicion_folios';

    protected $fillable = [
        'obra_id',
        'empresa_requisicion_tipo_id',
        'anio',
        'prefijo',
        'numero_catalogo',
        'ultimo_consecutivo',
    ];

    protected $casts = [
        'anio' => 'integer',
        'ultimo_consecutivo' => 'integer',
    ];

    public function obra()
    {
        return $this->belongsTo(Obra::class);
    }

    public function tipo()
    {
        return $this->belongsTo(EmpresaRequisicionTipo::class, 'empresa_requisicion_tipo_id');
    }

    public function requisiciones()
    {
        return $this->hasMany(ObraRequisicion::class, 'obra_requisicion_folio_id');
    }
}
