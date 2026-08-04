<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('empleado_epp_entregas', function (Blueprint $table) {
            $table->id();
            $table->integer('empleado_id');
            $table->string('articulo', 120);
            $table->decimal('cantidad', 8, 2)->default(1);
            $table->string('talla', 50)->nullable();
            $table->date('fecha_entrega');
            $table->string('condicion', 80)->default('nuevo');
            $table->string('obra_area', 150)->nullable();
            $table->foreignId('entregado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->text('observaciones')->nullable();
            $table->boolean('confirmado_por_empleado')->default(false);
            $table->timestamp('fecha_confirmacion')->nullable();
            $table->timestamps();

            $table->foreign('empleado_id')
                ->references('id_Empleado')
                ->on('empleados')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->index(['empleado_id', 'fecha_entrega']);
            $table->index('articulo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empleado_epp_entregas');
    }
};
