<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientePortal extends Model
{
    use HasFactory;

    protected $table = 'cliente_portales';

    protected $fillable = [
        'cliente_id',
        'link_acceso',
        'usuario',
        'password',
    ];

    protected $casts = [
        'password' => 'encrypted',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
}