<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('facturas', function (Blueprint $table) {
      $table->id();

      // Fuente / trazabilidad
      $table->string('source_system', 60)->default('CONTPAQi-ComercialStart');
      $table->unsignedBigInteger('doc_sat_id')->nullable(); // DocSATID local (no es único global)

      // Identidad CFDI
      $table->uuid('uuid'); // UUID CFDI (único)
      $table->string('serie', 20)->nullable();
      $table->string('folio', 30)->nullable();
      $table->string('tipo_comprobante', 5)->nullable();

      // Receptor/Emisor
      $table->string('rfc_emisor', 20)->index();
      $table->string('rfc_receptor', 20)->index();
      $table->string('razon_social', 255)->nullable(); // la que te trae CONTPAQi (normalmente receptor)

      // Fechas
      $table->dateTime('fecha_emision')->nullable()->index();
      $table->dateTime('fecha_timbrado')->nullable()->index(); // FechaCertificacion

      // Pago / CFDI metadata
      $table->string('moneda', 10)->nullable();
      $table->decimal('tipo_cambio', 18, 6)->nullable();

      $table->string('forma_pago', 10)->nullable();
      $table->string('metodo_pago', 10)->nullable();
      $table->string('uso_cfdi', 10)->nullable();

      // Importes
      $table->decimal('subtotal', 18, 2)->nullable();
      $table->decimal('descuento', 18, 2)->nullable();

      $table->decimal('iva_0', 18, 2)->nullable();
      $table->decimal('iva_8', 18, 2)->nullable();
      $table->decimal('iva_16', 18, 2)->nullable();

      $table->decimal('ieps', 18, 2)->nullable();
      $table->decimal('iva_retenido', 18, 2)->nullable();
      $table->decimal('isr_retenido', 18, 2)->nullable();

      $table->decimal('total', 18, 2)->nullable()->index();

      // Estado según CONTPAQi
      $table->string('status', 50)->nullable()->index(); // Vigente, Cancelado, etc.
      $table->dateTime('fecha_cancelacion')->nullable()->index();

      // XML (opcional, pero útil)
      $table->longText('xml')->nullable();

      $table->timestamps();

      // Unicidad: por si a futuro conectas otra razón social/base
      $table->unique(['source_system', 'uuid'], 'facturas_source_uuid_unique');

      // Para búsquedas comunes
      $table->index(['serie', 'folio']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('facturas');
  }
};