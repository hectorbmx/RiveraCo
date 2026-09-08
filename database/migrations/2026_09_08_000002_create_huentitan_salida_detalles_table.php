<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('huentitan_salida_detalles')) {
            Schema::create('huentitan_salida_detalles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('huentitan_salida_id')->constrained('huentitan_salidas')->cascadeOnDelete();
                $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
                $table->text('descripcion')->nullable();
                $table->string('unidad', 30)->nullable();
                $table->decimal('cantidad_solicitada', 14, 3)->default(0);
                $table->decimal('cantidad_salida', 14, 3)->default(0);
                $table->decimal('costo_unitario', 14, 4)->default(0);
                $table->decimal('importe', 14, 2)->default(0);
                $table->text('observaciones')->nullable();
                $table->timestamps();

                $table->index(['huentitan_salida_id', 'producto_id'], 'hue_sal_det_sal_prod_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('huentitan_salida_detalles');
    }
};
