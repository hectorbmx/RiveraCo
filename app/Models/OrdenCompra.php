<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ObraPlaneacionGasto;

class OrdenCompra extends Model
{
    protected $table = 'ordenes_compra';

    protected $fillable = [
        'folio',
        'proveedor_id',
        'obra_id',
        'centro_costo_id',
        'area_id',
        'area',
        'cotizacion',
        'atencion',
        'tipo_pago',
        'forma_pago',
        'subtotal',
        'iva',
        'otros_impuestos',
        'total',
        'tipo_cambio',
        'moneda',
        'fecha',
        'estado',
        'usuario_registro',
        'registrado_por',
        'usuario_autoriza',
        'autorizado_por',
        'fecha_autorizacion',
        'comentarios',
    ];

    protected $casts = [
        'fecha' => 'date',
        'fecha_autorizacion' => 'date',
        'subtotal' => 'decimal:2',
        'iva' => 'decimal:2',
        'otros_impuestos' => 'decimal:2',
        'total' => 'decimal:2',
        'tipo_cambio' => 'decimal:4',
    ];

    // Mapeo de estado legacy -> "nuevo"
    public function getEstadoNormalizadoAttribute(): string
    {
        $e = strtoupper((string)($this->estado ?? 'BORRADOR'));
        return match ($e) {
            'BORRADOR', 'PROGRAMADA' => 'programada',
            'AUTORIZADA' => 'autorizada',
            'CANCELADA' => 'cancelada',
            default => strtolower($e),
        };
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    public function obra()
    {
        return $this->belongsTo(Obra::class, 'obra_id');
    }

    public function centroCosto()
    {
        return $this->belongsTo(CentroCosto::class, 'centro_costo_id');
    }

    public function areaCatalogo()
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    public function detalles()
    {
        return $this->hasMany(OrdenCompraDetalle::class, 'orden_compra_id');
    }

    public function registradoPor()
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function autorizadoPor()
    {
        return $this->belongsTo(User::class, 'autorizado_por');
    }

    public function pagosProveedor()
    {
        return $this->hasMany(PagoProveedor::class, 'orden_compra_id');
    }

    public function pagoProveedorActivo()
    {
        return $this->hasOne(PagoProveedor::class, 'orden_compra_id')
            ->whereIn('estatus', ['programado', 'autorizado', 'pagado'])
            ->latestOfMany();
    }
    public function partida()
    {
        return $this->belongsTo(\App\Models\ObraPlaneacionGasto::class, 'planeacion_gasto_id');
    }
    public function planeacionGasto()
    {
        return $this->belongsTo(ObraPlaneacionGasto::class, 'planeacion_gasto_id');
    }
 
}
