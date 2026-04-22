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
    Schema::create('sat_cfdis', function (Blueprint $table) {
        $table->id();

        $table->foreignId('sat_download_request_id')
            ->constrained('sat_download_requests')
            ->cascadeOnDelete();

        $table->string('uuid', 50)->unique();

        $table->string('rfc_emisor', 13)->nullable();
        $table->string('rfc_receptor', 13)->nullable();

        $table->dateTime('fecha_emision')->nullable();

        $table->string('tipo_comprobante', 5)->nullable();
        $table->decimal('total', 18, 6)->nullable();

        $table->string('xml_path')->nullable();
        $table->string('package_id')->nullable();

        $table->timestamps();

        $table->index(['rfc_emisor', 'rfc_receptor']);
        $table->index(['fecha_emision']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sat_cfdis');
    }
};
