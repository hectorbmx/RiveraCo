<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehiculo_empleado', function (Blueprint $table) {
            $table->unsignedBigInteger('km_inicial')->after('fecha_asignacion');
            $table->unsignedBigInteger('km_final')->nullable()->after('km_inicial');
        });
    }

    public function down(): void
    {
        Schema::table('vehiculo_empleado', function (Blueprint $table) {
            $table->dropColumn(['km_inicial', 'km_final']);
        });
    }
};
