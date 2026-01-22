<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nomina_recibos', function (Blueprint $table) {
            $table->id();

            // Empleado (ojo: referencia a id_Empleado de empleados)
            $table->integer('empleado_id');
            $table->foreign('empleado_id')
                ->references('id_Empleado')
                ->on('empleados')
                ->cascadeOnDelete();

            // Obra (nueva relación) + nombre legacy de la vieja tabla
            $table->foreignId('obra_id')
                ->nullable()
                ->constrained('obras')
                ->nullOnDelete();

            $table->string('obra_legacy', 100)->nullable(); // nombre/código que venía en la tabla vieja

            // Información de periodo
            $table->string('periodo_label', 100)->nullable(); // "Semana 12 2024", "1a quincena Ene 2025", etc.
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();

            // Pago
            $table->date('fecha_pago')->nullable();

            // Totales "nuevos"
            $table->decimal('total_percepciones', 12, 2)->default(0);
            $table->decimal('total_deducciones', 12, 2)->default(0);
            $table->decimal('sueldo_neto', 12, 2)->default(0); // aquí mapearemos la columna "suma" (total pagado)

            // Estado del recibo
            $table->string('status', 20)->default('pagado'); // pagado, pendiente, cancelado

            // Referencias (folio de nómina, CFDI, etc.)
            $table->string('folio', 50)->nullable();
            $table->string('referencia_externa', 100)->nullable();

            /*
             *  Campos "legacy" de tu tabla vieja
             *  (para no perder detalle histórico)
             */

            // Días de falta
            $table->decimal('faltas', 6, 2)->nullable();

            // Descuentos y conceptos viejos
            $table->decimal('descuentos_legacy', 12, 2)->nullable();   // campo "descuentos"
            $table->decimal('infonavit_legacy', 12, 2)->nullable();    // campo "infonavit"

            // Horas extra trabajadas
            $table->decimal('horas_extra', 8, 2)->nullable();          // campo "tiemp_ext" (horas)

            // Monto por metros lineales (productividad)
            $table->decimal('metros_lin_monto', 12, 2)->nullable();    // campo "metros_lin"

            // Comisiones (monto)
            $table->decimal('comisiones_monto', 12, 2)->nullable();    // campo "comisiones"

            // Monto facturado por extras (campo "factura")
            $table->decimal('factura_monto', 12, 2)->nullable();

            // Notas del registro viejo
            $table->string('notas_legacy', 255)->nullable();

            $table->timestamps();

            // Índices útiles
            $table->index('empleado_id');
            $table->index('obra_id');
            $table->index('fecha_pago');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nomina_recibos');
    }
};
