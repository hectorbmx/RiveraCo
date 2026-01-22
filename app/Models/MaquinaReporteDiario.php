<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MaquinaReporteDiario extends Model
{
    use HasFactory;

    protected $table = 'maquinas_reporte_diario';

    protected $fillable = [
        'fecha',
        'obra_id',
        'maquina_id',
        'observaciones',
        'created_by',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    /* =======================
     |  Relaciones
     ======================= */

    public function obra()
    {
        return $this->belongsTo(Obra::class);
    }

    public function maquina()
    {
        return $this->belongsTo(Maquina::class);
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
