<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehiculo_empleado_km_logs', function (Blueprint $table): void {
            // Temporalmente permitimos capturar kilometraje sin evidencia fotografica.
            $table->string('foto')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('vehiculo_empleado_km_logs', function (Blueprint $table): void {
            $table->string('foto')->nullable(false)->change();
        });
    }
};