<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ObraFactura extends Model
{
    protected $table = 'obras_facturas';

    protected $fillable = [
        'obra_id',
        'fecha_factura',
        'fecha_pago',
        'monto',
        'estado',
        'pdf_path',
        'notas',
    ];

    protected $casts = [
        'fecha_factura' => 'date',
        'fecha_pago'    => 'date',
        'monto'         => 'decimal:2',
    ];

    public function obra()
    {
        return $this->belongsTo(Obra::class);
    }

    // Útil más adelante: saber si está pagada
    // public function getEstaPagadaAttribute(): bool
    // {
    //     return !is_null($this->fecha_pago);
    // }
      // Helpers de estado (por si los quieres usar en Blade)
    public function getEstaPendienteAttribute(): bool
    {
        return $this->estado === 'pendiente';
    }

    public function getEstaPagadaAttribute(): bool
    {
        return $this->estado === 'pagada';
    }

    public function getEstaCanceladaAttribute(): bool
    {
        return $this->estado === 'cancelada';
    }
}
