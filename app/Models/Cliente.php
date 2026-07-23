<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Obra;
use App\Models\Factura;

class Cliente extends Model
{
    use HasFactory;

    protected $fillable = [
    'nombre_comercial',
    'razon_social',
    'rfc',
    'telefono',
    'email',
    'direccion',
    'calle',
    'colonia',
    'ciudad',
    'estado',
    'pais',

    // SAT / CFDI
    'codigo_postal',
    'regimen_fiscal',
    'uso_cfdi_default',
    'facturapi_customer_id',

    'activo',
];

    // Relación con obras (la crearemos después)
    public function obras()
    {
        return $this->hasMany(Obra::class);
    }
    public function facturaBorradores()
    {
        return $this->hasMany(ObraFacturaBorrador::class);
    }

    public function facturas()
{
    return $this->hasMany(Factura::class,
        'rfc_receptor', // FK en facturas
        'rfc'           // PK lógica en clientes
    );
}
    public function portales()
    {
        return $this->hasMany(ClientePortal::class);
    }
}
