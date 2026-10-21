<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('obra_requisicion_detalles', function (Blueprint $table): void {
            $table->string('equipo_tipo', 30)->nullable()->after('observaciones');
            $table->foreignId('vehiculo_id')->nullable()->after('equipo_tipo')->constrained('vehiculos')->nullOnDelete();
            $table->foreignId('maquina_id')->nullable()->after('vehiculo_id')->constrained('maquinas')->nullOnDelete();
            $table->string('equipo_nombre_snapshot', 255)->nullable()->after('maquina_id');
            $table->string('equipo_identificador_snapshot', 120)->nullable()->after('equipo_nombre_snapshot');

            $table->index(['equipo_tipo', 'vehiculo_id'], 'obra_req_detalles_equipo_vehiculo_index');
            $table->index(['equipo_tipo', 'maquina_id'], 'obra_req_detalles_equipo_maquina_index');
        });
    }

    public function down(): void
    {
        Schema::table('obra_requisicion_detalles', function (Blueprint $table): void {
            $table->dropIndex('obra_req_detalles_equipo_vehiculo_index');
            $table->dropIndex('obra_req_detalles_equipo_maquina_index');
            $table->dropForeign(['vehiculo_id']);
            $table->dropForeign(['maquina_id']);
            $table->dropColumn([
                'equipo_tipo',
                'vehiculo_id',
                'maquina_id',
                'equipo_nombre_snapshot',
                'equipo_identificador_snapshot',
            ]);
        });
    }
};
