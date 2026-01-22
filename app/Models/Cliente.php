<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Obra;

class Cliente extends Model
{
    use HasFactory;

    protected $fillable = [
    'nombre_comercial',
    'razon_social',
    'rfc',
    'telefono',
    'email',
    'direccion',     // si la sigues usando como campo libre
    'calle',
    'colonia',
    'ciudad',
    'estado',
    'pais',
    'activo',
    ];

    // Relación con obras (la crearemos después)
    public function obras()
    {
        return $this->hasMany(Obra::class);
    }
    
}
