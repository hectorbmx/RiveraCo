<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReposicionCajaChicaSubcategoria extends Model
{
    use HasFactory;

    protected $table = 'reposicion_caja_chica_subcategorias';

    protected $fillable = [
        'categoria_id',
        'codigo',
        'nombre',
        'descripcion',
        'activo',
        'orden',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'orden' => 'integer',
    ];

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(ReposicionCajaChicaCategoria::class, 'categoria_id');
    }

    public function gastos(): HasMany
    {
        return $this->hasMany(ReposicionCajaChicaGasto::class, 'subcategoria_id');
    }
}
