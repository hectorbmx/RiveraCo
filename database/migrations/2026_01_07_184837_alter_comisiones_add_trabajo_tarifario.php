<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('comisiones', function (Blueprint $table) {
            // Si ya existen, comenta estas líneas
            $table->foreignId('trabajo_id')
                ->nullable()
                ->after('pila_id')
                ->constrained('catalogo_trabajos_comision')
                ->nullOnDelete();

            $table->foreignId('tarifario_id')
                ->nullable()
                ->after('trabajo_id')
                ->constrained('comision_tarifarios')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('comisiones', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tarifario_id');
            $table->dropConstrainedForeignId('trabajo_id');
        });
    }
};
