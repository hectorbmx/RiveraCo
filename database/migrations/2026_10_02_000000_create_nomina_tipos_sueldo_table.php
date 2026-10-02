<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('nomina_tipos_sueldo')) {
            return;
        }

        Schema::create('nomina_tipos_sueldo', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 80)->unique();
            $table->string('nombre', 100);
            $table->unsignedInteger('dias_periodo')->nullable();
            $table->decimal('factor_mensual', 8, 4)->nullable();
            $table->boolean('activo')->default(true);
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();

            $table->index(['activo', 'orden'], 'nomina_tipos_sueldo_activo_orden_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nomina_tipos_sueldo');
    }
};
