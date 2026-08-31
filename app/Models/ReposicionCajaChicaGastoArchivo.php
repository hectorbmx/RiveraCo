<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ReposicionCajaChicaGastoArchivo extends Model
{
    use HasFactory;

    protected $table = 'reposicion_caja_chica_gasto_archivos';

    protected $fillable = [
        'gasto_id',
        'tipo',
        'disk',
        'path',
        'nombre_original',
        'mime_type',
        'size_bytes',
        'hash_sha256',
        'uploaded_by',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
    ];

    protected $appends = ['url'];

    public function gasto(): BelongsTo
    {
        return $this->belongsTo(ReposicionCajaChicaGasto::class, 'gasto_id');
    }

    public function getUrlAttribute(): ?string
    {
        return $this->path ? Storage::disk($this->disk ?? 'public')->url($this->path) : null;
    }
}
