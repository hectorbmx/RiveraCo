<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Support\Facades\DB;

class EmpresaSecurityController extends Controller
{
    /**
     * Helpers para regresar al tab correcto sin perder UX.
     */
    private function backToTab(string $tab)
    {
        return redirect()
            ->route('empresa_config.edit', ['tab' => $tab]);
    }

    private function forgetSpatieCache(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    // =========================
    // ROLES
    // =========================

    public function roleStore(Request $request)
    {
        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:150',
                Rule::unique('roles', 'name'),
            ],
            'guard_name' => ['nullable', 'string', 'max:50'],
        ]);

        $guard = $data['guard_name'] ?: 'web';

        Role::create([
            'name' => $data['name'],
            'guard_name' => $guard,
        ]);

        $this->forgetSpatieCache();

        return $this->backToTab('roles')->with('ok', 'Rol creado.');
    }

    public function roleUpdate(Request $request, Role $role)
    {
        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:150',
                Rule::unique('roles', 'name')->ignore($role->id),
            ],
        ]);

        // Guard no lo cambiamos aquí para evitar inconsistencias.
        $role->update([
            'name' => $data['name'],
        ]);

        $this->forgetSpatieCache();

        return $this->backToTab('roles')->with('ok', 'Rol actualizado.');
    }

    public function roleDestroy(Role $role)
    {
        // Si el rol está asignado a usuarios, NO se debe borrar.
        $assignedUsers = DB::table('model_has_roles')
            ->where('role_id', $role->id)
            ->count();

        if ($assignedUsers > 0) {
            return $this->backToTab('roles')
                ->with('err', "No se puede eliminar. Está asignado a {$assignedUsers} usuario(s).");
        }

        // Si tiene permisos, no es “bloqueante”, pero limpiamos relaciones.
        $role->syncPermissions([]);

        $role->delete();

        $this->forgetSpatieCache();

        return $this->backToTab('roles')->with('ok', 'Rol eliminado.');
    }

    // =========================
    // ROLES -> PERMISOS
    // =========================

    public function roleSyncPermissions(Request $request, Role $role)
    {
        $data = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => ['integer'],
        ]);

        $permissionIds = $data['permissions'] ?? [];

        // Para evitar asignar ids inválidos:
        $permissions = Permission::query()
            ->where('guard_name', $role->guard_name)
            ->whereIn('id', $permissionIds)
            ->get();

        $role->syncPermissions($permissions);

        $this->forgetSpatieCache();

        return $this->backToTab('roles')->with('ok', 'Permisos del rol actualizados.');
    }

    // =========================
    // PERMISOS
    // =========================

    public function permissionStore(Request $request)
    {
        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:150',
                Rule::unique('permissions', 'name'),
            ],
            'guard_name' => ['nullable', 'string', 'max:50'],
        ]);

        $guard = $data['guard_name'] ?: 'web';

        Permission::create([
            'name' => $data['name'],
            'guard_name' => $guard,
        ]);

        $this->forgetSpatieCache();

        return $this->backToTab('permisos')->with('ok', 'Permiso creado.');
    }

    public function permissionUpdate(Request $request, Permission $permission)
    {
        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:150',
                Rule::unique('permissions', 'name')->ignore($permission->id),
            ],
        ]);

        // Guard no lo cambiamos aquí para no romper asignaciones.
        $permission->update([
            'name' => $data['name'],
        ]);

        $this->forgetSpatieCache();

        return $this->backToTab('permisos')->with('ok', 'Permiso actualizado.');
    }

    public function permissionDestroy(Permission $permission)
    {
        // Bloqueo: si está asignado a roles, no se elimina.
        $rolesCount = DB::table('role_has_permissions')
            ->where('permission_id', $permission->id)
            ->count();

        if ($rolesCount > 0) {
            return $this->backToTab('permisos')
                ->with('err', "No se puede eliminar. Está asignado a {$rolesCount} rol(es).");
        }

        // Si hubiera permisos directos a usuarios (model_has_permissions), también bloquear:
        $usersCount = DB::table('model_has_permissions')
            ->where('permission_id', $permission->id)
            ->count();

        if ($usersCount > 0) {
            return $this->backToTab('permisos')
                ->with('err', "No se puede eliminar. Está asignado directamente a {$usersCount} usuario(s).");
        }

        $permission->delete();

        $this->forgetSpatieCache();

        return $this->backToTab('permisos')->with('ok', 'Permiso eliminado.');
    }

    public function permissionsSeedModules()
{
    $modules = [
        'dashboard.view',

        'clientes.access',
        'obras.access',
        'vehiculos.access',
        'mantenimiento.access',
        'empleados.access',
        'nomina.access',
        'ordenes_compra.access',
        'productos.access',
        'proveedores.access',
        'reportes.access',
        'empresa.access',
        'usuarios_app.access',
    ];

    foreach ($modules as $name) {
        Permission::firstOrCreate([
            'name' => $name,
            'guard_name' => 'web',
        ]);
    }

    $this->forgetSpatieCache();

    return $this->backToTab('permisos')->with('ok', 'Permisos base generados.');
}
}
