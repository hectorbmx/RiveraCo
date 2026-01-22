<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordenes_compra', function (Blueprint $table) {
            $table->id();

            // Folio del sistema viejo (id_orden en legacy)
            $table->string('folio', 50)->index();

            // Relación con proveedor (usa tabla 'proveedores' nueva)
            $table->foreignId('proveedor_id')
                  ->constrained('proveedores')
                  ->cascadeOnUpdate()
                  ->restrictOnDelete();

            // Relación opcional con obra (asumo que ya tienes tabla 'obras')
            $table->foreignId('obra_id')
                  ->nullable()
                  ->constrained('obras')
                  ->cascadeOnUpdate()
                  ->nullOnDelete();

            // Datos que puedes mapear desde legacy:
            // cotizacion, atencion, area, tipo de pago, forma de pago
            $table->string('cotizacion', 50)->nullable();
            $table->string('atencion', 100)->nullable();
            $table->string('area', 100)->nullable();
            $table->string('tipo_pago', 50)->nullable();   // pagotipo legacy
            $table->string('forma_pago', 50)->nullable();  // pagoForma legacy

            // Importes (los llenaremos al importar o al crear la OC)
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('iva', 12, 2)->default(0);
            $table->decimal('otros_impuestos', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);

            // Tipo de cambio (por si en legacy lo usabas)
            $table->decimal('tipo_cambio', 10, 4)->nullable();

            // Fecha de la orden
            $table->date('fecha')->nullable();

            // Estado de la OC (mapear desde 'estatus' legacy)
            // ejemplo: BORRADOR, ENVIADA, APROBADA, CANCELADA, etc.
            $table->string('estado', 20)->default('BORRADOR');

            // Quién la registró (de momento guardamos el texto legacy)
            $table->string('usuario_registro', 50)->nullable(); // usr_registro legacy

            // Datos de autorización (si en legacy se usaban)
            $table->string('usuario_autoriza', 50)->nullable(); // aut2 legacy
            $table->date('fecha_autorizacion')->nullable();     // fecha_aut legacy

            // Comentarios generales
            $table->text('comentarios')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordenes_compra');
    }
};
