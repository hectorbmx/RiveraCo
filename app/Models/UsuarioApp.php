<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UsuarioApp extends Model
{
    use SoftDeletes;

    protected $table = 'usuarios_app';

    protected $fillable = [
        'user_id',
        'empleado_id',
        'activation_token',
        'activated_at',
        'is_active',
        // OJO: por ahora NO incluyo password aquí (aunque exista en tabla),
        // porque no lo vamos a usar para auth móvil.
    ];

    protected $casts = [
        'activated_at' => 'datetime',
        'is_active'    => 'boolean',
    ];

    // =======================
    // Relaciones
    // =======================

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function empleado()
    {
        // Tu tabla empleados usa PK "id_Empleado" (según screenshot)
        // y en usuarios_app guardas empleado_id (int).
        return $this->belongsTo(Empleado::class, 'empleado_id', 'id_Empleado');
    }
}
