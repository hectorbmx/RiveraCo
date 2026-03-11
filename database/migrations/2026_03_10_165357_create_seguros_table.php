<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seguros', function (Blueprint $table) {
            $table->id();

            // Relación polimórfica: Maquina / Vehiculo / futuro otro activo
            $table->string('asegurable_type');
            $table->unsignedBigInteger('asegurable_id');

            // Datos principales de la póliza
            $table->string('aseguradora');
            $table->string('poliza_numero');
            $table->string('tipo_seguro')->nullable(); // amplia, RC, daños, equipo, etc.

            // Administrativos / financieros
            $table->string('metodo_pago')->nullable();
            $table->string('frecuencia_pago')->nullable();
            $table->decimal('costo', 14, 2)->default(0);
            $table->string('moneda', 10)->default('MXN');

            // Fechas
            $table->date('fecha_compra')->nullable();
            $table->date('vigencia_desde');
            $table->date('vigencia_hasta');

            // Cobertura
            $table->decimal('suma_asegurada', 14, 2)->nullable();
            $table->decimal('deducible', 14, 2)->nullable();
            $table->text('cobertura')->nullable();

            // Estado administrativo
            $table->string('estatus')->default('vigente');

            // Alertas
            $table->boolean('alerta_vencimiento_activa')->default(true);
            $table->integer('dias_preaviso')->default(30);
            $table->timestamp('ultima_alerta_enviada_at')->nullable();

            // Archivos
            $table->string('documento_path')->nullable();
            $table->string('comprobante_path')->nullable();

            // Observaciones
            $table->text('observaciones')->nullable();

            // Auditoría
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index(['asegurable_type', 'asegurable_id'], 'seguros_asegurable_index');
            $table->index(['vigencia_hasta'], 'seguros_vigencia_hasta_index');
            $table->index(['estatus'], 'seguros_estatus_index');
            $table->index(['alerta_vencimiento_activa'], 'seguros_alerta_activa_index');

            $table->unique(
                ['asegurable_type', 'asegurable_id', 'poliza_numero', 'vigencia_desde'],
                'seguros_unique_poliza_por_activo'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seguros');
    }
};