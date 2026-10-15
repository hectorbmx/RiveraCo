<?php

use Spatie\Permission\Models\Permission;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const OLD_NAME = 'obras.vehiculos.combustible.delete.access.access';
    private const NEW_NAME = 'obras.vehiculos.combustible.delete.access';

    public function up(): void
    {
        $permission = Permission::query()
            ->where('name', self::OLD_NAME)
            ->where('guard_name', 'web')
            ->first();

        if ($permission) {
            $permission->update(['name' => self::NEW_NAME]);
        } else {
            Permission::firstOrCreate([
                'name' => self::NEW_NAME,
                'guard_name' => 'web',
            ]);
        }
    }

    public function down(): void
    {
        Permission::query()
            ->where('name', self::NEW_NAME)
            ->where('guard_name', 'web')
            ->update(['name' => self::OLD_NAME]);
    }
};