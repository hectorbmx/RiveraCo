<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('comision_personal_actividades', function (Blueprint $table) {
            $table->id();

            $table->foreignId('comision_personal_id')
                ->constrained('comision_personal')
                ->cascadeOnDelete();

            $table->foreignId('actividad_id')
                ->constrained('catalogo_actividades_comision')
                ->restrictOnDelete();

            $table->timestamps();

            $table->unique(['comision_personal_id', 'actividad_id'], 'uniq_personal_actividad');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comision_personal_actividades');
    }
};
