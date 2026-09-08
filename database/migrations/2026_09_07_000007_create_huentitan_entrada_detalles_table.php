<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('huentitan_entrada_detalles')) {
            Schema::create('huentitan_entrada_detalles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('huentitan_entrada_id')->constrained('huentitan_entradas')->cascadeOnDelete();
                $table->foreignId('orden_compra_detalle_id')->nullable()->constrained('orden_compra_detalles')->nullOnDelete();
                $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
                $table->text('descripcion')->nullable();
                $table->string('unidad', 30)->nullable();
                $table->decimal('cantidad_ordenada', 14, 3)->default(0);
                $table->decimal('cantidad_recibida', 14, 3)->default(0);
                $table->decimal('costo_unitario', 14, 4)->default(0);
                $table->decimal('importe', 14, 2)->default(0);
                $table->text('observaciones')->nullable();
                $table->timestamps();

                $table->index(['huentitan_entrada_id', 'producto_id'], 'hue_ent_det_ent_prod_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('huentitan_entrada_detalles');
    }
};
