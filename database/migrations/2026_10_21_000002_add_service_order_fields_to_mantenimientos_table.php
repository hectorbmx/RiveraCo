<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mantenimientos', function (Blueprint $table): void {
            $table->string('orden_servicio_folio', 40)->nullable()->unique()->after('estatus');
            $table->dateTime('orden_servicio_generada_at')->nullable()->after('orden_servicio_folio');
            $table->foreignId('orden_servicio_generada_por')->nullable()->after('orden_servicio_generada_at')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('mantenimientos', function (Blueprint $table): void {
            $table->dropForeign(['orden_servicio_generada_por']);
            $table->dropUnique(['orden_servicio_folio']);
            $table->dropColumn(['orden_servicio_folio', 'orden_servicio_generada_at', 'orden_servicio_generada_por']);
        });
    }
};
