<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NominaListaRaya extends Model
{
    use HasFactory;

    protected $table = 'nomina_listas_raya';

    public const TIPO_OBRA = 'obra';
    public const TIPO_AREA = 'area';
    public const TIPO_ALMACEN = 'almacen';
    public const TIPO_OFICINA = 'oficina';
    public const TIPO_OPERATIVA = 'operativa';

    public const TIPOS = [
        self::TIPO_OBRA => 'Obra',
        self::TIPO_AREA => 'Area',
        self::TIPO_ALMACEN => 'Almacen',
        self::TIPO_OFICINA => 'Oficina',
        self::TIPO_OPERATIVA => 'Operativa',
    ];

    protected $fillable = [
        'nombre',
        'tipo',
        'area_id',
        'obra_id',
        'almacen_id',
        'activo',
        'es_automatica',
        'orden',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'es_automatica' => 'boolean',
        'orden' => 'integer',
    ];

    public function area()
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    public function obra()
    {
        return $this->belongsTo(Obra::class, 'obra_id');
    }

    public function almacen()
    {
        return $this->belongsTo(Almacen::class, 'almacen_id');
    }

    public function empleadosPrincipales()
    {
        return $this->hasMany(Empleado::class, 'lista_raya_principal_id', 'id');
    }
}
