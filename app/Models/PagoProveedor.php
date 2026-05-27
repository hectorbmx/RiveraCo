<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PagoProveedor extends Model
{
    protected $table = 'pagos_proveedores';

    protected $fillable = [
        'orden_compra_id',
        'proveedor_id',
        'cuenta_banco_empresa_id',
        'fecha_programada',
        'fecha_pago',
        'monto',
        'moneda',
        'metodo_pago',
        'referencia',
        'observaciones',
        'estatus',
        'programado_by',
        'autorizado_by',
        'autorizado_at',
        'pagado_by',
        'pagado_at',
    ];

    protected $casts = [
        'fecha_programada' => 'date',
        'fecha_pago' => 'date',
        'monto' => 'decimal:2',
        'autorizado_at' => 'datetime',
        'pagado_at' => 'datetime',
    ];

    public function ordenCompra()
    {
        return $this->belongsTo(OrdenCompra::class, 'orden_compra_id');
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    public function cuentaBancoEmpresa()
    {
        return $this->belongsTo(CuentaBancoEmpresa::class, 'cuenta_banco_empresa_id');
    }

    public function programadoPor()
    {
        return $this->belongsTo(User::class, 'programado_by');
    }
}
