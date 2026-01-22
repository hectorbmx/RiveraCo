<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MaquinariaReporteSnapshot extends Model
{
    use HasFactory;

    protected $table = 'maquinaria_reporte_snapshots';

    protected $fillable = [
        'fecha',
        'estado',
        'total_maquinas',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function items()
    {
        return $this->hasMany(
            MaquinariaReporteSnapshotItem::class,
            'snapshot_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopePorFecha($query, string $fecha)
    {
        return $query->whereDate('fecha', $fecha);
    }
}
