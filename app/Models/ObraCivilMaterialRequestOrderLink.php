<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ObraCivilMaterialRequestOrderLink extends Model
{
    public const STATUS_BORRADOR = 'borrador';
    public const STATUS_AUTORIZADA = 'autorizada';
    public const STATUS_CANCELADA = 'cancelada';

    protected $table = 'obra_civil_material_request_order_links';

    protected $fillable = [
        'obra_civil_material_request_id',
        'orden_compra_id',
        'status',
        'created_by',
        'metadata',
    ];

    protected $casts = [
        'obra_civil_material_request_id' => 'integer',
        'orden_compra_id' => 'integer',
        'created_by' => 'integer',
        'metadata' => 'array',
    ];

    public function materialRequest()
    {
        return $this->belongsTo(ObraCivilMaterialRequest::class, 'obra_civil_material_request_id');
    }

    public function ordenCompra()
    {
        return $this->belongsTo(OrdenCompra::class, 'orden_compra_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
