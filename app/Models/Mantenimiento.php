<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mantenimiento extends Model
{
    protected $table = 'mantenimientos';

    protected $fillable = [
        'vehiculo_id',
        'maquina_id',
        'obra_id',
        'tipo',
        'categoria_mantenimiento',
        'servicio_preventivo_tipo_id',
        'descripcion',
        'km_actuales',
        'km_proximo_servicio',
        'horometro',
        'fecha_programada',
        'fecha_inicio',
        'fecha_fin',
        'estatus',
        'abastecimiento_tipo',
        'abastecimiento_estado',
        'mecanico_id',
        'costo_mano_obra',
        'costo_refacciones',
        'costo_total',
        'notas',
    ];

    protected $casts = [
        'fecha_programada' => 'datetime',
        'fecha_inicio'     => 'datetime',
        'fecha_fin'        => 'datetime',
    ];

    public const ABASTECIMIENTO_SURTIDO_DIRECTO = 'surtido_directo';
    public const ABASTECIMIENTO_COMPRA = 'compra';
    public const ABASTECIMIENTO_PARCIAL = 'parcial';

    /* ----------------- RELACIONES ----------------- */

    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class, 'vehiculo_id');
    }

    public function maquina()
    {
        return $this->belongsTo(Maquina::class, 'maquina_id');
    }

    public function obra()
    {
        return $this->belongsTo(Obra::class, 'obra_id');
    }

    // Mecánico (empleado que atendió el servicio)
    public function mecanico()
    {
        return $this->belongsTo(Empleado::class, 'mecanico_id', 'id_Empleado');
    }

    public function servicioPreventivoTipo()
    {
        return $this->belongsTo(EmpresaServicioPreventivoTipo::class, 'servicio_preventivo_tipo_id');
    }

    public function detalles()
    {
        return $this->hasMany(MantenimientoDetalle::class, 'mantenimiento_id');
    }

    public function abastecimientos()
    {
        return $this->hasMany(MantenimientoAbastecimiento::class, 'mantenimiento_id');
    }

    public function fotos()
    {
        return $this->hasMany(MantenimientoFoto::class, 'mantenimiento_id');
    }

    /* ----------------- HELPERS ----------------- */

    public function getDuracionEnMinutosAttribute()
    {
        if (!$this->fecha_inicio || !$this->fecha_fin) {
            return null;
        }

        $minutos = $this->fecha_inicio->diffInMinutes($this->fecha_fin, false);

        return $minutos > 0 ? $minutos : null;
    }
}
