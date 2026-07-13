<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SatFacturaBorrador extends Model
{
    protected $table = 'sat_factura_borradores';

    protected $fillable = [
        'user_id',
        'sat_empresa_id',
        'cliente_id',
        'obra_id',
        'obra_factura_borrador_id',
        'sat_factura_id',
        'titulo',
        'payload',
        'estado',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

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

    public function factura()
    {
        return $this->belongsTo(SatFactura::class, 'sat_factura_id');
    }

    public function obraFacturaBorrador()
    {
        return $this->belongsTo(ObraFacturaBorrador::class, 'obra_factura_borrador_id');
    }
}