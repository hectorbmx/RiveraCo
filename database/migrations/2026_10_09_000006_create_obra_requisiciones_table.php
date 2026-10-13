<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('obra_requisiciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('obra_id')->constrained('obras')->restrictOnDelete();
            $table->foreignId('empresa_requisicion_tipo_id')->constrained('empresa_requisicion_tipos')->restrictOnDelete();
            $table->foreignId('obra_requisicion_folio_id')->nullable()->constrained('obra_requisicion_folios')->nullOnDelete();
            $table->string('folio', 40);
            $table->unsignedSmallInteger('anio');
            $table->string('numero_catalogo', 2);
            $table->unsignedInteger('consecutivo');
            $table->string('estado', 40)->default('pendiente_revision');
            $table->date('fecha_solicitud');
            $table->date('fecha_requerida')->nullable();
            $table->foreignId('solicitado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('solicitado_at')->nullable();
            $table->foreignId('autorizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('autorizado_at')->nullable();
            $table->foreignId('rechazado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rechazado_at')->nullable();
            $table->foreignId('surtido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('surtido_at')->nullable();
            $table->foreignId('recibido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('recibido_at')->nullable();
            $table->text('observaciones')->nullable();
            $table->text('motivo_rechazo')->nullable();
            $table->text('motivo_cancelacion')->nullable();
            $table->timestamps();

            $table->unique(['obra_id', 'folio'], 'obra_requisiciones_obra_folio_unique');
            $table->index(['obra_id', 'estado'], 'obra_requisiciones_obra_estado_index');
            $table->index(['empresa_requisicion_tipo_id', 'estado'], 'obra_requisiciones_tipo_estado_index');
            $table->index(['anio', 'numero_catalogo', 'consecutivo'], 'obra_requisiciones_folio_partes_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('obra_requisiciones');
    }
};
