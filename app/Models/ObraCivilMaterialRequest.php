<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ObraCivilMaterialRequest extends Model
{
    use HasFactory;

    public const STATUS_BORRADOR = 'borrador';
    public const STATUS_ENVIADA = 'enviada';
    public const STATUS_EN_REVISION = 'en_revision';
    public const STATUS_APROBADA = 'aprobada';
    public const STATUS_APROBADA_PARCIAL = 'aprobada_parcial';
    public const STATUS_RECHAZADA = 'rechazada';
    public const STATUS_CONVERTIDA_A_OC = 'convertida_a_oc';
    public const STATUS_CANCELADA = 'cancelada';

    protected $fillable = [
        'obra_id',
        'user_id',
        'empleado_id',
        'folio',
        'status',
        'notes',
        'submitted_at',
        'reviewed_by',
        'reviewed_at',
        'orden_compra_id',
        'metadata',
    ];

    protected $casts = [
        'empleado_id' => 'integer',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'orden_compra_id' => 'integer',
        'metadata' => 'array',
    ];

    public function obra()
    {
        return $this->belongsTo(Obra::class, 'obra_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'empleado_id', 'id_Empleado');
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function ordenCompra()
    {
        return $this->belongsTo(OrdenCompra::class, 'orden_compra_id');
    }

    public function items()
    {
        return $this->hasMany(ObraCivilMaterialRequestItem::class, 'obra_civil_material_request_id');
    }

    public function approvedItems()
    {
        return $this->hasMany(ObraCivilMaterialRequestItem::class, 'obra_civil_material_request_id')
            ->where('approved_quantity', '>', 0);
    }

    public function orderLinks()
    {
        return $this->hasMany(ObraCivilMaterialRequestOrderLink::class, 'obra_civil_material_request_id');
    }

    public function ordenesCompra()
    {
        return $this->belongsToMany(
            OrdenCompra::class,
            'obra_civil_material_request_order_links',
            'obra_civil_material_request_id',
            'orden_compra_id'
        )
            ->withPivot(['status', 'created_by', 'metadata'])
            ->withTimestamps();
    }
}
