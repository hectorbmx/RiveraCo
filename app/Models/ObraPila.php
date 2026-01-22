<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ObraPila extends Model
{
    use HasFactory;

    protected $table = 'obras_pilas';

    protected $fillable = [
        'obra_id',
        // 'numero_pila',
        'tipo',
        'diametro_proyecto',
        'profundidad_proyecto',
        'ubicacion',
        'activo',
        'notas',
        'cantidad_programada',
    ];

    protected $casts = [
        'diametro_proyecto'   => 'decimal:2',
        'profundidad_proyecto'=> 'decimal:2',
        'activo'              => 'boolean',
        'cantidad_programada' => 'integer',
        'created_at'          => 'datetime',
        'updated_at'          => 'datetime',
    ];

    public function obra()
    {
        return $this->belongsTo(Obra::class, 'obra_id');
    }

    public function comisiones()
    {
        return $this->hasMany(Comision::class, 'pila_id');
    }

    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }
    public function detallesComision()
{
    return $this->hasManyThrough(
        ComisionDetalle::class,   // modelo final
        Comision::class,          // modelo intermedio
        'pila_id',                // FK en comisiones que apunta a obras_pilas.id
        'comision_id',            // FK en comision_detalles que apunta a comisiones.id
        'id',                     // PK local en obras_pilas
        'id'                      // PK en comisiones
    );
}
}
