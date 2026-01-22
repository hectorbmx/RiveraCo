<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('obras_facturas', function (Blueprint $table) {
            // Agregamos la columna estado después de monto
            $table->string('estado', 20)
                  ->default('pendiente')
                  ->after('monto');
        });

        // Backfill de datos existentes:
        // - Si ya tienen fecha_pago => marcamos como 'pagada'
        // - Si no => dejamos 'pendiente' (default)
        DB::table('obras_facturas')
            ->whereNotNull('fecha_pago')
            ->update(['estado' => 'pagada']);
    }

    public function down(): void
    {
        Schema::table('obras_facturas', function (Blueprint $table) {
            $table->dropColumn('estado');
        });
    }
};
