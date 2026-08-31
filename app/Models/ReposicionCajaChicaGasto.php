<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReposicionCajaChicaGasto extends Model
{
    use HasFactory;

    protected $table = 'reposicion_caja_chica_gastos';

    protected $fillable = [
        'relacion_id',
        'categoria_id',
        'subcategoria_id',
        'destino',
        'obra_id',
        'almacen_id',
        'fecha_gasto',
        'proveedor_nombre',
        'proveedor_id',
        'proveedor_rfc',
        'concepto',
        'forma_pago',
        'importe_registrado',
        'importe_autorizado',
        'estado_autorizacion',
        'motivo_sin_factura',
        'observaciones',
        'resuelto_por',
        'resuelto_at',
        'motivo_rechazo',
        'observaciones_autorizacion',
        'solicitado_por',
        'solicitado_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'fecha_gasto' => 'date',
        'importe_registrado' => 'decimal:2',
        'importe_autorizado' => 'decimal:2',
        'resuelto_at' => 'datetime',
        'solicitado_at' => 'datetime',
    ];

    public function relacion(): BelongsTo
    {
        return $this->belongsTo(ReposicionCajaChicaRelacion::class, 'relacion_id');
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(ReposicionCajaChicaCategoria::class, 'categoria_id');
    }

    public function subcategoria(): BelongsTo
    {
        return $this->belongsTo(ReposicionCajaChicaSubcategoria::class, 'subcategoria_id');
    }

    public function obra(): BelongsTo
    {
        return $this->belongsTo(Obra::class, 'obra_id');
    }

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class, 'almacen_id');
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    public function archivos(): HasMany
    {
        return $this->hasMany(ReposicionCajaChicaGastoArchivo::class, 'gasto_id');
    }

    public function solicitadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitado_por');
    }

    public function resueltoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resuelto_por');
    }
}
