<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('sat_download_requests', function (Blueprint $table) {
        $table->id();

        // Usuario que ejecuta la descarga (opcional si es multiusuario)
        $table->unsignedBigInteger('user_id')->nullable();

        // RFC que solicita (FIEL usada)
        $table->string('rfc_solicitante', 13);

        // Parámetros de consulta
   $table->dateTime('fecha_inicio');
$table->dateTime('fecha_fin');

        // Tipo: received | issued
        $table->string('tipo_descarga', 20);

        // requestId del SAT
        $table->string('request_id_sat')->nullable();

        // paquetes devueltos por SAT
        $table->json('packages_ids')->nullable();

        // total de xml descargados
        $table->integer('total_xml')->default(0);

        // estados del flujo
        $table->enum('estado', [
            'pending',
            'querying',
            'verifying',
            'downloading',
            'completed',
            'failed'
        ])->default('pending');

        // errores
        $table->text('error_message')->nullable();

        $table->timestamps();

        // índice útil
        $table->index(['rfc_solicitante', 'estado']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sat_download_requests');
    }
};
