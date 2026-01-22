<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComisionDetalle extends Model
{
    use HasFactory;

    protected $table = 'comision_detalles';

    protected $fillable = [
        'comision_id',
        'obra_maquina_id',
        'diametro',
        'cantidad',
        'profundidad',
        'metros_sujetos_comision',
        'kg_acero',
        'vol_bentonita',
        'vol_concreto',
        'ml_ademe_bauer',
        'campana_pzas',
        'adicional',
    ];

    protected $casts = [
        'cantidad'               => 'integer',
        'profundidad'            => 'decimal:2',
        'metros_sujetos_comision'=> 'decimal:2',
        'kg_acero'               => 'decimal:2',
        'vol_bentonita'          => 'decimal:2',
        'vol_concreto'           => 'decimal:2',
        'ml_ademe_bauer'         => 'decimal:2',
        'campana_pzas'           => 'decimal:2',
        'created_at'             => 'datetime',
        'updated_at'             => 'datetime',
    ];

    public function comision()
    {
        return $this->belongsTo(Comision::class, 'comision_id');
    }
    

    public function asignacionMaquina()
    {
        return $this->belongsTo(ObraMaquina::class, 'obra_maquina_id');
    }
    public function pila()
    {
        return $this->belongsTo(ObraPila::class, 'pila_id');
    }
     public function catalogoPila()
    {
        // pila_id apunta a catalogo_pilas.id
        return $this->belongsTo(CatalogoPila::class, 'pila_id');
    }

}
