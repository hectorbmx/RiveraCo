<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CatalogoPila extends Model
{
    use HasFactory;

    protected $table = 'catalogo_pilas';

    protected $fillable = [
        'codigo',
        'descripcion',
        'diametro_cm',
        'activa',
    ];

    protected $casts = [
        'activa' => 'boolean',
    ];

    // Más adelante aquí podemos poner relaciones con ObraPila
    // public function obrasPilas()
    // {
    //     return $this->hasMany(ObraPila::class, 'catalogo_pila_id');
    // }
}
