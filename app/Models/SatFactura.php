<?php

namespace App\Models;
use App\Models\Cliente;
use App\Models\Obra;
use App\Models\OrdenCompra;
use App\Models\SatFacturaConcepto;
use Illuminate\Database\Eloquent\Model;
use App\Models\SatFacturaPago;

class SatFactura extends Model
{
    protected $table = 'sat_facturas';

    protected $fillable = [

        // Relaciones
        'sat_empresa_id',
        'cliente_id',
        'obra_id',
        'orden_compra_id',

        // PAC
        'facturapi_invoice_id',
        'facturapi_customer_id',

        // CFDI
        'uuid',
        'serie',
        'folio',
        'tipo_comprobante',
        'cfdi_version',

        // Receptor
        'receptor_rfc',
        'receptor_nombre',
        'receptor_regimen',
        'receptor_cp',
        'uso_cfdi',

        // Pago
        'metodo_pago',
        'forma_pago',
        'moneda',
        'tipo_cambio',

        // Importes
        'subtotal',
        'descuento',
        'iva',
        'retenciones',
        'total',

        // Estado
        'estado',
        'fecha_emision',
        'fecha_timbrado',
        'fecha_cancelacion',

        // Archivos
        'xml_path',
        'pdf_path',

        // Debug
        'facturapi_response',
        'error_message',
        // Email
        'email_enviado_at',
'email_destino',
    ];

    protected $casts = [
        'facturapi_response' => 'array',

        'fecha_emision' => 'datetime',
        'fecha_timbrado' => 'datetime',
        'fecha_cancelacion' => 'datetime',

        'subtotal' => 'decimal:2',
        'descuento' => 'decimal:2',
        'iva' => 'decimal:2',
        'retenciones' => 'decimal:2',
        'total' => 'decimal:2',
        'tipo_cambio' => 'decimal:6',
        'email_enviado_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELACIONES
    |--------------------------------------------------------------------------
    */

    public function empresa()
    {
        return $this->belongsTo(SatEmpresa::class, 'sat_empresa_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function obra()
    {
        return $this->belongsTo(Obra::class);
    }

    public function ordenCompra()
    {
        return $this->belongsTo(OrdenCompra::class);
    }
    public function conceptos()
    {
        return $this->hasMany(SatFacturaConcepto::class, 'sat_factura_id');
    }
    public function pagos()
    {
        return $this->hasMany(SatFacturaPago::class, 'sat_factura_id');
    }
}