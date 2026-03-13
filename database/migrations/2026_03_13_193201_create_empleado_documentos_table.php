<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empleado_documentos', function (Blueprint $table) {
            $table->id();

            $table->integer('empleado_id');

            $table->enum('tipo_documento', [
                'INE',
                'LICENCIA_CONDUCIR',
                'COMPROBANTE_DOMICILIO',
                'ACTA_NACIMIENTO',
                'CURP',
                'RFC',
                'NSS',
                'CONSTANCIA_FISCAL',
                'CONTRATO',
                'OTRO'
            ]);

            $table->string('nombre_documento')->nullable();

            $table->string('archivo_path');
            $table->string('archivo_nombre_original')->nullable();
            $table->string('mime_type')->nullable();
            $table->string('extension', 10)->nullable();
            $table->unsignedBigInteger('tamano_bytes')->nullable();

            $table->date('fecha_documento')->nullable();
            $table->date('fecha_vencimiento')->nullable();

            $table->boolean('vigente')->default(true);

            $table->enum('estatus_validacion', [
                'pendiente',
                'validado',
                'rechazado'
            ])->default('pendiente');

            $table->unsignedBigInteger('validado_por')->nullable();
            $table->timestamp('validado_en')->nullable();

            $table->text('observaciones')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('empleado_id')
                ->references('id_Empleado')
                ->on('empleados')
                ->onDelete('cascade');

            $table->index(['empleado_id', 'tipo_documento']);
            $table->index(['empleado_id', 'vigente']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empleado_documentos');
    }
};