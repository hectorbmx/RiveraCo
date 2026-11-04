<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaquinaPreventivoReferencia extends Model
{
    protected $table = 'maquina_preventivo_referencias';

    protected $fillable = ['maquina_id', 'servicio_preventivo_tipo_id', 'horometro', 'meta_horometro', 'fecha_referencia', 'motivo', 'created_by'];

    protected $casts = ['horometro' => 'decimal:2', 'meta_horometro' => 'decimal:2', 'fecha_referencia' => 'datetime'];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function servicio()
    {
        return $this->belongsTo(EmpresaServicioPreventivoTipo::class, 'servicio_preventivo_tipo_id');
    }
}
