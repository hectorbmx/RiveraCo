<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orden_compra_detalles', function (Blueprint $table) {
            $table->id();

            // Relación con la cabecera
            $table->foreignId('orden_compra_id')
                  ->constrained('ordenes_compra')
                  ->cascadeOnUpdate()
                  ->cascadeOnDelete();

            // Relación con producto (nuevo)
            $table->foreignId('producto_id')
                  ->nullable()
                  ->constrained('productos')
                  ->cascadeOnUpdate()
                  ->nullOnDelete();

            // Por si quieres conservar el código/texto legacy tal cual
            $table->string('legacy_prod_id', 50)->nullable(); // prod_id legacy

            // Descripción editable a nivel renglón
            $table->string('descripcion', 255)->nullable();   // concepto legacy
            $table->string('unidad', 50)->nullable();         // unidad legacy

            // Cantidades y precios
            $table->decimal('cantidad', 12, 3)->default(0);          // cantidad legacy
            $table->decimal('precio_unitario', 12, 4)->default(0);   // precio o total_unit legacy
            $table->decimal('importe', 12, 2)->default(0);           // importe legacy

            // Impuestos por renglón (si los usabas; si no, siempre 0)
            $table->decimal('iva', 12, 2)->default(0);
            $table->decimal('retenciones', 12, 2)->default(0);
            $table->decimal('otros_impuestos', 12, 2)->default(0);

            // Moneda / tipo de cambio (por si en el futuro lo quieres manejar por línea)
            $table->decimal('tipo_cambio', 10, 4)->nullable();

            // Notas específicas de la línea
            $table->string('notas', 255)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orden_compra_detalles');
    }
};
