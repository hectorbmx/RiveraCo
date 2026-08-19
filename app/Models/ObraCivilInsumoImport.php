<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ObraCivilInsumoImport extends Model
{
    use HasFactory;

    protected $fillable = [
        'obra_id',
        'filename',
        'original_path',
        'sheet_name',
        'status',
        'total_insumos',
        'total_materiales',
        'total_mano_obra',
        'total_equipo_herramienta',
        'total_importe',
        'imported_by',
        'metadata',
    ];

    protected $casts = [
        'total_insumos' => 'integer',
        'total_materiales' => 'integer',
        'total_mano_obra' => 'integer',
        'total_equipo_herramienta' => 'integer',
        'total_importe' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function obra()
    {
        return $this->belongsTo(Obra::class, 'obra_id');
    }

    public function insumos()
    {
        return $this->hasMany(ObraCivilInsumo::class, 'obra_civil_insumo_import_id');
    }

    public function importedBy()
    {
        return $this->belongsTo(User::class, 'imported_by');
    }
}
