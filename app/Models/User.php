<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use App\Models\UsuarioApp;
use App\Models\Empleado;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable,HasRoles;
    protected $guard_name = 'web';
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
    public function usuarioApp()
        {
            return $this->hasOne(UsuarioApp::class, 'user_id','id');
        }

        // Opcional: acceso directo User->empleado (hasOneThrough)
        public function empleado()
        {
            return $this->hasOneThrough(
                Empleado::class,
                UsuarioApp::class,
                'user_id',       // FK en usuarios_app que apunta a users.id
                'id_Empleado',   // PK en empleados
                'id',            // PK local en users
                'empleado_id'    // FK en usuarios_app que apunta a empleados.id_Empleado
            );
        }
}
