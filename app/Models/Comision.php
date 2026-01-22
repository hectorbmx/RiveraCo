<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comision extends Model
{
    use HasFactory;

    protected $table = 'comisiones';

    protected $fillable = [
        'obra_id',
        'pila_id',
        'trabajo_id',
        'tarifario_id',
        'fecha',
        'residente_id',
        // 'residente_id' => ['nullable', 'exists:empleados,id_Empleado'],

        'numero_formato',
        'cliente_nombre',
        'observaciones',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'fecha'      => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function obra()
    {
        return $this->belongsTo(Obra::class, 'obra_id');
    }

    public function pila()
    {
        return $this->belongsTo(ObraPila::class, 'pila_id');
    }

    // Aunque la FK no existe en DB, a nivel modelo sí podemos usarla
    public function residente()
    {
        return $this->belongsTo(Empleado::class, 'residente_id','id_Empleado');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function personales()
    {
        return $this->hasMany(ComisionPersonal::class, 'comision_id');
    }

    public function detalles()
    {
        return $this->hasMany(ComisionDetalle::class, 'comision_id');
    }

    public function perforaciones()
    {
        return $this->hasMany(ComisionPerforacion::class, 'comision_id');
    }
       public function catalogoPila()
    {
        // pila_id -> catalogo_pilas.id
        return $this->belongsTo(CatalogoPila::class, 'pila_id');
    }
    public function tarifario()
    {
        return $this->belongsTo(ComisionTarifario::class, 'comision_tarifario_id');
    }
    
   
}
