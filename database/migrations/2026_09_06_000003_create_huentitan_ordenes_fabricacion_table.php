<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('huentitan_ordenes_fabricacion', function (Blueprint $table) {
            $table->id();
            $table->string('folio', 40)->unique();
            $table->foreignId('producto_id')->nullable()->constrained('productos')->restrictOnDelete();
            $table->decimal('cantidad_solicitada', 14, 3)->default(0);
            $table->date('fecha');
            $table->string('estado', 40)->default('borrador');
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('estado', 'hof_estado_index');
            $table->index('fecha', 'hof_fecha_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('huentitan_ordenes_fabricacion');
    }
};
