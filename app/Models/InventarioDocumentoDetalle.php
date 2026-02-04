<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventarioDocumentoDetalle extends Model
{
    protected $table = 'inventario_documento_detalles';

    protected $fillable = [
        'documento_id',
        'producto_id',
        'cantidad',
        'costo_unitario',
        'notas',
    ];

    protected $casts = [
        'cantidad'      => 'decimal:3',
        'costo_unitario'=> 'decimal:4',
    ];

    public function documento(): BelongsTo
    {
        return $this->belongsTo(InventarioDocumento::class, 'documento_id');
    }

    // Ajustar si tu modelo no se llama Producto::class
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
