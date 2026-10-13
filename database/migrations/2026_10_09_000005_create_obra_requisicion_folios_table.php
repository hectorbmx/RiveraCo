<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('obra_requisicion_folios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('obra_id')->constrained('obras')->restrictOnDelete();
            $table->foreignId('empresa_requisicion_tipo_id')->constrained('empresa_requisicion_tipos')->restrictOnDelete();
            $table->unsignedSmallInteger('anio');
            $table->string('prefijo', 10)->default('REQ');
            $table->string('numero_catalogo', 2);
            $table->unsignedInteger('ultimo_consecutivo')->default(0);
            $table->timestamps();

            $table->unique(
                ['obra_id', 'empresa_requisicion_tipo_id', 'anio'],
                'obra_req_folios_obra_tipo_anio_unique'
            );
            $table->index(['anio', 'numero_catalogo'], 'obra_req_folios_anio_catalogo_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('obra_requisicion_folios');
    }
};
