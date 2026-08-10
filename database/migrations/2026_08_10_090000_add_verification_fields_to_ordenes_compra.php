<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordenes_compra', function (Blueprint $table) {
            if (!Schema::hasColumn('ordenes_compra', 'usuario_verifica')) {
                $table->string('usuario_verifica', 50)
                    ->nullable()
                    ->after('fecha_autorizacion');
            }

            if (!Schema::hasColumn('ordenes_compra', 'verificado_por')) {
                $table->foreignId('verificado_por')
                    ->nullable()
                    ->after('usuario_verifica')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('ordenes_compra', 'fecha_verificacion')) {
                $table->timestamp('fecha_verificacion')
                    ->nullable()
                    ->after('verificado_por');
            }
        });

        $permission = Permission::firstOrCreate([
            'name' => 'ordenes_compra.verify.access',
            'guard_name' => 'web',
        ]);

        $super = Role::where('name', 'super-admin')->first();
        if ($super) {
            $super->givePermissionTo($permission);
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::table('ordenes_compra', function (Blueprint $table) {
            if (Schema::hasColumn('ordenes_compra', 'fecha_verificacion')) {
                $table->dropColumn('fecha_verificacion');
            }

            if (Schema::hasColumn('ordenes_compra', 'verificado_por')) {
                $table->dropConstrainedForeignId('verificado_por');
            }

            if (Schema::hasColumn('ordenes_compra', 'usuario_verifica')) {
                $table->dropColumn('usuario_verifica');
            }
        });
    }
};