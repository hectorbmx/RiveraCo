<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reposicion_caja_chica_relaciones', function (Blueprint $table) {
            $table->id();
            $table->string('folio', 40)->unique();
            $table->unsignedSmallInteger('semana_anio')->nullable();
            $table->unsignedTinyInteger('semana_numero')->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->foreignId('responsable_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('area_codigo', 30)->nullable();
            $table->foreignId('almacen_id')->nullable()->constrained('almacenes')->nullOnDelete();
            $table->string('estado', 40)->default('borrador');
            $table->timestamp('fecha_generacion')->nullable();
            $table->decimal('total_registrado', 14, 2)->default(0);
            $table->decimal('total_autorizado', 14, 2)->default(0);
            $table->decimal('total_rechazado', 14, 2)->default(0);
            $table->decimal('total_pendiente', 14, 2)->default(0);
            $table->decimal('monto_reposicion', 14, 2)->default(0);
            $table->unsignedBigInteger('programacion_pago_id')->nullable();
            $table->timestamp('pagado_at')->nullable();
            $table->string('referencia_pago')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['semana_anio', 'semana_numero']);
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reposicion_caja_chica_relaciones');
    }
};
