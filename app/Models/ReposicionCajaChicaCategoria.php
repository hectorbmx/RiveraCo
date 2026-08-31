<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReposicionCajaChicaCategoria extends Model
{
    use HasFactory;

    protected $table = 'reposicion_caja_chica_categorias';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'requiere_factura',
        'requiere_xml',
        'forma_pago_base',
        'activo',
        'orden',
    ];

    protected $casts = [
        'requiere_factura' => 'boolean',
        'requiere_xml' => 'boolean',
        'activo' => 'boolean',
        'orden' => 'integer',
    ];

    public function subcategorias(): HasMany
    {
        return $this->hasMany(ReposicionCajaChicaSubcategoria::class, 'categoria_id');
    }

    public function gastos(): HasMany
    {
        return $this->hasMany(ReposicionCajaChicaGasto::class, 'categoria_id');
    }
}
