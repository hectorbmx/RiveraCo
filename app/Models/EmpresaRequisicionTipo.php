<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmpresaRequisicionTipo extends Model
{
    use HasFactory;

    protected $table = 'empresa_requisicion_tipos';

    protected $fillable = [
        'codigo',
        'numero_catalogo',
        'nombre',
        'descripcion',
        'origen_catalogo',
        'item_type',
        'almacen_id',
        'requiere_catalogo',
        'permite_manual',
        'activo',
        'orden',
    ];

    protected $casts = [
        'almacen_id' => 'integer',
        'requiere_catalogo' => 'boolean',
        'permite_manual' => 'boolean',
        'activo' => 'boolean',
        'orden' => 'integer',
    ];

    public function almacen()
    {
        return $this->belongsTo(Almacen::class, 'almacen_id');
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeOrdenados($query)
    {
        return $query
            ->orderBy('orden')
            ->orderBy('numero_catalogo')
            ->orderBy('nombre');
    }
}
