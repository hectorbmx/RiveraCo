<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('huentitan_entradas')) {
            Schema::create('huentitan_entradas', function (Blueprint $table) {
                $table->id();
                $table->string('folio', 50)->unique();
                $table->foreignId('almacen_id')->constrained('almacenes')->cascadeOnUpdate()->restrictOnDelete();
                $table->foreignId('orden_compra_id')->nullable()->constrained('ordenes_compra')->cascadeOnUpdate()->nullOnDelete();
                $table->string('tipo_origen', 30)->default('orden_compra'); // orden_compra, ajuste, devolucion_obra, produccion, otro
                $table->dateTime('fecha');
                $table->string('estado', 20)->default('borrador'); // borrador, aplicada, cancelada
                $table->foreignId('usuario_id')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
                $table->foreignId('aplicada_por')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
                $table->dateTime('fecha_aplicacion')->nullable();
                $table->foreignId('cancelada_por')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
                $table->dateTime('fecha_cancelacion')->nullable();
                $table->text('motivo_cancelacion')->nullable();
                $table->text('observaciones')->nullable();
                $table->timestamps();

                $table->index(['estado', 'fecha'], 'hue_ent_est_fec_idx');
                $table->index('tipo_origen', 'hue_ent_tipo_orig_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('huentitan_entradas');
    }
};
