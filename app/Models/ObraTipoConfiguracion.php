<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ObraTipoConfiguracion extends Model
{
    use HasFactory;

    protected $table = 'obra_tipo_configuraciones';

    protected $fillable = [
        'tipo_obra',
        'label',
        'prefijo',
        'area_id',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function area()
    {
        return $this->belongsTo(Area::class, 'area_id');
    }
}
