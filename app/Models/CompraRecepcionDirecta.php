<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompraRecepcionDirecta extends Model
{
    public const PERMISO_VER = 'ordenes_compra.direct_receipts.view.access';
    public const PERMISO_CONFIRMAR = 'ordenes_compra.direct_receipts.confirm.access';
    public const PERMISO_REVERTIR = 'ordenes_compra.direct_receipts.reverse.access';

    public const ESTADO_BORRADOR = 'borrador';
    public const ESTADO_APLICADA = 'aplicada';
    public const ESTADO_REVERTIDA = 'revertida';

    protected $table = 'compra_recepciones_directas';

    protected $fillable = [
        'operacion_uuid', 'solicitud_hash', 'folio', 'orden_compra_id', 'obra_id', 'almacen_id', 'estado',
        'fecha_recepcion', 'receptor_nombre', 'remision', 'observaciones', 'moneda',
        'tipo_cambio', 'total_oc', 'total_mxn', 'entrada_documento_id', 'salida_documento_id',
        'confirmado_por', 'confirmado_at', 'revertido_por', 'revertido_at', 'motivo_reversion',
    ];

    protected $casts = [
        'fecha_recepcion' => 'date', 'confirmado_at' => 'datetime', 'revertido_at' => 'datetime',
        'tipo_cambio' => 'decimal:8', 'total_oc' => 'decimal:2', 'total_mxn' => 'decimal:2',
    ];

    public function ordenCompra(): BelongsTo { return $this->belongsTo(OrdenCompra::class); }
    public function obra(): BelongsTo { return $this->belongsTo(Obra::class); }
    public function almacen(): BelongsTo { return $this->belongsTo(Almacen::class); }
    public function entrada(): BelongsTo { return $this->belongsTo(InventarioDocumento::class, 'entrada_documento_id'); }
    public function salida(): BelongsTo { return $this->belongsTo(InventarioDocumento::class, 'salida_documento_id'); }
    public function confirmadoPor(): BelongsTo { return $this->belongsTo(User::class, 'confirmado_por'); }
    public function revertidoPor(): BelongsTo { return $this->belongsTo(User::class, 'revertido_por'); }
    public function detalles(): HasMany { return $this->hasMany(CompraRecepcionDirectaDetalle::class, 'recepcion_id'); }
    public function archivos(): HasMany { return $this->hasMany(CompraRecepcionDirectaArchivo::class, 'recepcion_id'); }
    public function documentos(): HasMany { return $this->hasMany(InventarioDocumento::class, 'compra_recepcion_directa_id'); }
    public function movimientos(): HasMany { return $this->hasMany(InventarioMovimiento::class, 'compra_recepcion_directa_id'); }
}
