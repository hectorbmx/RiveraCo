<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuarios_app', function (Blueprint $table) {
            // 1) vínculo con users (identidad)
            $table->foreignId('user_id')
                ->nullable()
                ->after('id')
                ->constrained('users')
                ->nullOnDelete();

            // 2) bandera simple de acceso móvil
            $table->boolean('is_active')
                ->default(false)
                ->after('activated_at');

            // 3) asegurar 1-1 entre users y usuarios_app
            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('usuarios_app', function (Blueprint $table) {
            $table->dropUnique(['user_id']);
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn('is_active');
        });
    }
};
