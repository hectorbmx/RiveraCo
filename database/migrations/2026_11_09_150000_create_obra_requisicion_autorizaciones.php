<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('obra_requisicion_autorizaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('obra_requisicion_id');
            $table->foreignId('usuario_id');
            $table->timestamps();
            $table->foreign('obra_requisicion_id', 'ora_req_fk')->references('id')->on('obra_requisiciones')->restrictOnDelete();
            $table->foreign('usuario_id', 'ora_usuario_fk')->references('id')->on('users')->restrictOnDelete();
        });
        Schema::create('obra_requisicion_autorizacion_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('autorizacion_id');
            $table->foreignId('requisicion_detalle_id');
            $table->decimal('cantidad_anterior', 12, 3);
            $table->decimal('cantidad_nueva', 12, 3);
            $table->timestamps();
            $table->foreign('autorizacion_id', 'orad_aut_fk')->references('id')->on('obra_requisicion_autorizaciones')->restrictOnDelete();
            $table->foreign('requisicion_detalle_id', 'orad_det_fk')->references('id')->on('obra_requisicion_detalles')->restrictOnDelete();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('obra_requisicion_autorizacion_detalles');
        Schema::dropIfExists('obra_requisicion_autorizaciones');
    }
};
