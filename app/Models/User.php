<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Collection;
use App\Models\UsuarioApp;
use App\Models\Empleado;
use App\Models\PhoneExtension;
use App\Models\PhoneCall;
use App\Models\TelephonyCallRequest;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles {
        HasRoles::getAllPermissions as spatieGetAllPermissions;
    }
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
        public function phoneExtensions()
        {
            return $this->hasMany(PhoneExtension::class);
        }

        public function phoneCalls()
        {
            return $this->hasMany(PhoneCall::class);
        }
        public function deniedPermissions()
        {
            return $this->belongsToMany(Permission::class, 'user_permission_overrides', 'user_id', 'permission_id')
                ->wherePivot('effect', 'deny')
                ->withTimestamps();
        }

        public function hasDeniedPermission(string $permission): bool
        {
            return $this->deniedPermissions()
                ->where('name', $permission)
                ->where('guard_name', $this->guard_name)
                ->exists();
        }

        public function getAllPermissions(): Collection
        {
            $permissions = $this->spatieGetAllPermissions();
            $deniedIds = $this->deniedPermissions()->pluck('permissions.id');

            if ($deniedIds->isEmpty()) {
                return $permissions;
            }

            return $permissions
                ->reject(fn (Permission $permission) => $deniedIds->contains($permission->id))
                ->values();
        }
}
