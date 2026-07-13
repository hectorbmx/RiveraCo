<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    private array $permissions = [
        'nomina.corridas.close.access',
        'nomina.corridas.pay.access',
        'nomina.corridas.reopen.access',
        'nomina.corridas.delete.access',
    ];

    public function up(): void
    {
        foreach ($this->permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $this->assignPermissions();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Keep permissions on rollback to avoid unexpectedly removing role capabilities in shared environments.
    }

    private function assignPermissions(): void
    {
        $permissions = Permission::whereIn('name', $this->permissions)->get();

        foreach (['super-admin', 'admin-rivera'] as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo($permissions);
            }
        }
    }
};