<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSatFacturasTable extends Migration
{
    public function up()
    {
        Schema::create('sat_facturas', function (Blueprint $table) {
            $table->id();

            // Empresa emisora local
            $table->foreignId('sat_empresa_id')
                ->constrained('sat_empresas')
                ->cascadeOnDelete();

            // Relaciones opcionales del ERP
            $table->foreignId('cliente_id')
                ->nullable()
                ->constrained('clientes')
                ->nullOnDelete();

            $table->foreignId('obra_id')
                ->nullable()
                ->constrained('obras')
                ->nullOnDelete();

            $table->foreignId('orden_compra_id')
                ->nullable()
                ->constrained('ordenes_compra')
                ->nullOnDelete();

            // Facturapi / PAC
            $table->string('facturapi_invoice_id')->nullable()->index();
            $table->string('facturapi_customer_id')->nullable();

            // CFDI
            $table->string('uuid')->nullable()->index();
            $table->string('serie', 20)->nullable();
            $table->string('folio', 50)->nullable();

            $table->string('tipo_comprobante', 5)->default('I'); // I, E, P, T, N
            $table->string('cfdi_version', 10)->nullable();

            // Receptor snapshot
            $table->string('receptor_rfc', 13)->nullable();
            $table->string('receptor_nombre')->nullable();
            $table->string('receptor_regimen', 10)->nullable();
            $table->string('receptor_cp', 10)->nullable();
            $table->string('uso_cfdi', 10)->nullable();

            // Pago
            $table->string('metodo_pago', 10)->nullable(); // PUE, PPD
            $table->string('forma_pago', 10)->nullable();  // 03, 99, etc.
            $table->string('moneda', 10)->default('MXN');
            $table->decimal('tipo_cambio', 12, 6)->default(1);

            // Importes
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('descuento', 15, 2)->default(0);
            $table->decimal('iva', 15, 2)->default(0);
            $table->decimal('retenciones', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);

            // Estado interno
            $table->string('estado', 30)->default('borrador');
            // borrador, timbrada, cancelada, error

            $table->timestamp('fecha_emision')->nullable();
            $table->timestamp('fecha_timbrado')->nullable();
            $table->timestamp('fecha_cancelacion')->nullable();

            // Archivos
            $table->string('xml_path')->nullable();
            $table->string('pdf_path')->nullable();

            // Debug / respuesta PAC
            $table->json('facturapi_response')->nullable();
            $table->text('error_message')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('sat_facturas');
    }
}