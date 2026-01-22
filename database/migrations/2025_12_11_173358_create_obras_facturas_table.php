<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('obras_facturas', function (Blueprint $table) {
            $table->id();

            // Relación con obras
            $table->foreignId('obra_id')
                  ->constrained('obras')
                  ->cascadeOnDelete();

            // Datos de facturación
            $table->date('fecha_factura');
            $table->date('fecha_pago')->nullable();
            $table->decimal('monto', 12, 2);

            // Ruta del PDF en el disco (storage/app/public/...)
            $table->string('pdf_path')->nullable();

            // Campo libre por si quieres notas/comentarios
            $table->text('notas')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('obras_facturas');
    }
};
