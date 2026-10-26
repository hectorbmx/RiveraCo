<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empleado_hora_extra_tarifas', function (Blueprint $table) {
            $table->id();
            $table->integer('empleado_id');
            $table->enum('modalidad', ['automatica', 'cuota_fija']);
            // Importe final por hora extra; null en modalidad automatica.
            $table->decimal('costo_hora_extra', 12, 4)->nullable();
            $table->date('vigente_desde');
            $table->date('vigente_hasta')->nullable();
            $table->text('motivo')->nullable();
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->foreign('empleado_id')
                ->references('id_Empleado')
                ->on('empleados')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->index(['empleado_id', 'vigente_desde', 'vigente_hasta'], 'empleado_he_tarifas_vigencia_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empleado_hora_extra_tarifas');
    }
};
