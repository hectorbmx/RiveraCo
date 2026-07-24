<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cliente_documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('documento_tipo_id')->constrained('empresa_documento_tipos')->restrictOnDelete();
            $table->string('tipo_documento', 80);
            $table->string('nombre_documento')->nullable();
            $table->string('archivo_path');
            $table->string('archivo_nombre_original')->nullable();
            $table->string('mime_type', 120)->nullable();
            $table->string('extension', 20)->nullable();
            $table->unsignedBigInteger('tamano_bytes')->nullable();
            $table->date('fecha_documento')->nullable();
            $table->date('fecha_vencimiento')->nullable();
            $table->boolean('vigente')->default(true);
            $table->string('estatus_validacion', 30)->default('pendiente');
            $table->foreignId('validado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validado_en')->nullable();
            $table->text('observaciones')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['cliente_id', 'documento_tipo_id', 'vigente']);
            $table->index(['cliente_id', 'estatus_validacion']);
            $table->index('fecha_vencimiento');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cliente_documentos');
    }
};