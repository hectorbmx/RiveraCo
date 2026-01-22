<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('obra_empleado', function (Blueprint $table) {
            // Nullable para no romper datos existentes; luego hacemos backfill y lo puedes endurecer.
            $table->foreignId('rol_id')
                ->nullable()
                ->after('puesto_en_obra')
                ->constrained('catalogo_roles')
                ->nullOnDelete();

            $table->index(['obra_id', 'rol_id']);
        });
    }

    public function down(): void
    {
        Schema::table('obra_empleado', function (Blueprint $table) {
            $table->dropIndex(['obra_id', 'rol_id']);
            $table->dropConstrainedForeignId('rol_id');
        });
    }
};
