<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('comision_personal', function (Blueprint $table) {
            $table->foreignId('rol_id')
                ->nullable()
                ->after('rol') // tu campo rol actual (varchar)
                ->constrained('catalogo_roles')
                ->nullOnDelete();

            // Para cálculo/pago
            $table->decimal('importe_comision', 12, 2)
                ->nullable()
                ->after('tiempo_extra');

            $table->index(['comision_id', 'rol_id']);
        });
    }

    public function down(): void
    {
        Schema::table('comision_personal', function (Blueprint $table) {
            $table->dropIndex(['comision_id', 'rol_id']);
            $table->dropColumn('importe_comision');
            $table->dropConstrainedForeignId('rol_id');
        });
    }
};
