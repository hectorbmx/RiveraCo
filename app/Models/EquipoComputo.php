<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EquipoComputo extends Model
{
    use SoftDeletes;

    public const ESTATUS_ACTIVO = 'activo';
    public const ESTATUS_ASIGNADO = 'asignado';
    public const ESTATUS_MANTENIMIENTO = 'mantenimiento';
    public const ESTATUS_RESGUARDO = 'resguardo';
    public const ESTATUS_BAJA = 'baja';

    protected $table = 'equipos_computo';

    protected $fillable = [
        'codigo_inventario',
        'tipo',
        'marca',
        'modelo',
        'numero_serie',
        'precio',
        'fecha_compra',
        'factura_folio',
        'factura_uuid',
        'factura_path',
        'resguardo_path',
        'ubicacion',
        'area_id',
        'responsable_actual_id',
        'estatus',
        'notas',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'fecha_compra' => 'date',
    ];

    public function area()
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    public function responsableActual()
    {
        return $this->belongsTo(Empleado::class, 'responsable_actual_id', 'id_Empleado');
    }

    public function movimientos()
    {
        return $this->hasMany(EquipoComputoMovimiento::class, 'equipo_computo_id')
            ->orderByDesc('fecha_movimiento')
            ->orderByDesc('created_at');
    }

    public function fotos()
    {
        return $this->hasMany(EquipoComputoFoto::class, 'equipo_computo_id')
            ->latest();
    }

    public function getNombreAttribute(): string
    {
        return trim($this->marca . ' ' . ($this->modelo ?? ''));
    }
}
