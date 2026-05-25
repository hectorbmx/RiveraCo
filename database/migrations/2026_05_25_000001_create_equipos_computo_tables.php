<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipos_computo', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_inventario', 80)->nullable()->unique();
            $table->string('tipo', 40)->default('laptop');
            $table->string('marca', 120);
            $table->string('modelo', 120)->nullable();
            $table->string('numero_serie', 160)->nullable()->unique();
            $table->decimal('precio', 14, 2)->nullable();
            $table->date('fecha_compra')->nullable();
            $table->string('factura_folio', 120)->nullable();
            $table->string('factura_uuid', 80)->nullable();
            $table->string('factura_path')->nullable();
            $table->string('ubicacion', 160)->nullable();
            $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete();
            $table->integer('responsable_actual_id')->nullable();
            $table->string('estatus', 40)->default('activo');
            $table->text('notas')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('responsable_actual_id')
                ->references('id_Empleado')
                ->on('empleados')
                ->nullOnDelete();

            $table->index('tipo');
            $table->index('estatus');
            $table->index('responsable_actual_id');
        });

        Schema::create('equipo_computo_movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipo_computo_id')->constrained('equipos_computo')->cascadeOnDelete();
            $table->string('tipo', 50);
            $table->integer('responsable_anterior_id')->nullable();
            $table->integer('responsable_nuevo_id')->nullable();
            $table->foreignId('area_anterior_id')->nullable()->constrained('areas')->nullOnDelete();
            $table->foreignId('area_nueva_id')->nullable()->constrained('areas')->nullOnDelete();
            $table->string('ubicacion_anterior', 160)->nullable();
            $table->string('ubicacion_nueva', 160)->nullable();
            $table->string('estatus_anterior', 40)->nullable();
            $table->string('estatus_nuevo', 40)->nullable();
            $table->date('fecha_movimiento');
            $table->text('notas')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->foreign('responsable_anterior_id')
                ->references('id_Empleado')
                ->on('empleados')
                ->nullOnDelete();

            $table->foreign('responsable_nuevo_id')
                ->references('id_Empleado')
                ->on('empleados')
                ->nullOnDelete();

            $table->index(['equipo_computo_id', 'fecha_movimiento'], 'eq_comp_mov_equipo_fecha_idx');
            $table->index('tipo', 'eq_comp_mov_tipo_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipo_computo_movimientos');
        Schema::dropIfExists('equipos_computo');
    }
};
