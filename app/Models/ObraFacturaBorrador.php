<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ObraFacturaBorrador extends Model
{
    public const ESTATUS_PENDIENTE_REVISION = 'pendiente_revision';
    public const ESTATUS_AUTORIZADO = 'autorizado';
    public const ESTATUS_RECHAZADO = 'rechazado';
    public const ESTATUS_FACTURADO = 'facturado';
    public const ESTATUS_CANCELADO = 'cancelado';

    protected $table = 'obra_factura_borradores';

    protected $fillable = [
        'obra_id',
        'cliente_id',
        'fecha',
        'forma_pago',
        'metodo_pago',
        'uso_cfdi',
        'regimen_fiscal',
        'sat_concepto_id',
        'concepto_descripcion',
        'cantidad',
        'subtotal',
        'iva_tasa',
        'iva',
        'retencion_tipo',
        'retenciones',
        'descuentos',
        'total',
        'estatus',
        'creado_por',
        'autorizado_por',
        'autorizado_at',
        'rechazado_por',
        'rechazado_at',
        'facturado_por',
        'facturado_at',
        'observaciones_revision',
        'sat_factura_id',
        'sat_cfdi_id',
    ];

    protected $casts = [
        'fecha' => 'date',
        'cantidad' => 'decimal:6',
        'subtotal' => 'decimal:2',
        'iva_tasa' => 'decimal:6',
        'iva' => 'decimal:2',
        'retenciones' => 'decimal:2',
        'descuentos' => 'decimal:2',
        'total' => 'decimal:2',
        'autorizado_at' => 'datetime',
        'rechazado_at' => 'datetime',
        'facturado_at' => 'datetime',
    ];

    public static function estatusLabels(): array
    {
        return [
            self::ESTATUS_PENDIENTE_REVISION => 'Pendiente de revision',
            self::ESTATUS_AUTORIZADO => 'Autorizado',
            self::ESTATUS_RECHAZADO => 'Rechazado',
            self::ESTATUS_FACTURADO => 'Facturado',
            self::ESTATUS_CANCELADO => 'Cancelado',
        ];
    }

    public static function retencionTipoLabels(): array
    {
        return [
            'sin_retencion' => 'Sin retencion',
            'iva' => 'Retencion IVA',
            'isr' => 'Retencion ISR',
            'iva_isr' => 'Retencion IVA + ISR',
            'otra' => 'Otra / manual',
        ];
    }

    public function obra()
    {
        return $this->belongsTo(Obra::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function conceptoSat()
    {
        return $this->belongsTo(SatConcepto::class, 'sat_concepto_id');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function autorizador()
    {
        return $this->belongsTo(User::class, 'autorizado_por');
    }

    public function rechazador()
    {
        return $this->belongsTo(User::class, 'rechazado_por');
    }

    public function facturador()
    {
        return $this->belongsTo(User::class, 'facturado_por');
    }

    public function satFactura()
    {
        return $this->belongsTo(SatFactura::class, 'sat_factura_id');
    }

    public function satCfdi()
    {
        return $this->belongsTo(SatCfdi::class, 'sat_cfdi_id');
    }
}
