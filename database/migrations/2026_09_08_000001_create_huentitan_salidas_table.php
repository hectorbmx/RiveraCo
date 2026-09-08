<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('huentitan_salidas')) {
            Schema::create('huentitan_salidas', function (Blueprint $table) {
                $table->id();
                $table->string('folio', 50)->unique();
                $table->foreignId('almacen_id')->constrained('almacenes')->cascadeOnUpdate()->restrictOnDelete();
                $table->string('tipo_destino', 30)->default('obra'); // obra, ajuste, otro
                $table->foreignId('obra_id')->nullable()->constrained('obras')->cascadeOnUpdate()->nullOnDelete();
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

                $table->index(['estado', 'fecha'], 'hue_sal_est_fec_idx');
                $table->index('tipo_destino', 'hue_sal_tipo_dest_idx');
                $table->index(['tipo_destino', 'obra_id'], 'hue_sal_dest_obra_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('huentitan_salidas');
    }
};
