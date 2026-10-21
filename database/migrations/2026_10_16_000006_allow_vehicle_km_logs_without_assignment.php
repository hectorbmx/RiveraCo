<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehiculo_empleado_km_logs', function (Blueprint $table): void {
            $table->unsignedBigInteger('vehiculo_id')->nullable()->after('vehiculo_empleado_id');
            $table->foreign('vehiculo_id')
                ->references('id')
                ->on('vehiculos')
                ->nullOnDelete();
        });

        // Preserve the vehicle context of historical logs before the assignment becomes optional.
        DB::statement(<<<'SQL'
            UPDATE vehiculo_empleado_km_logs AS logs
            INNER JOIN vehiculo_empleado AS asignaciones
                ON asignaciones.id = logs.vehiculo_empleado_id
            SET logs.vehiculo_id = asignaciones.vehiculo_id
            WHERE logs.vehiculo_id IS NULL
        SQL);

        DB::statement('ALTER TABLE vehiculo_empleado_km_logs MODIFY vehiculo_empleado_id BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        if (DB::table('vehiculo_empleado_km_logs')->whereNull('vehiculo_empleado_id')->exists()) {
            throw new RuntimeException('No se puede revertir: existen registros de kilometraje sin asignación.');
        }

        DB::statement('ALTER TABLE vehiculo_empleado_km_logs MODIFY vehiculo_empleado_id BIGINT UNSIGNED NOT NULL');

        Schema::table('vehiculo_empleado_km_logs', function (Blueprint $table): void {
            $table->dropForeign(['vehiculo_id']);
            $table->dropColumn('vehiculo_id');
        });
    }
};
