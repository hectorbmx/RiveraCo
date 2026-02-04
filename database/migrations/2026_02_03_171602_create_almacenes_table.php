<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('almacenes', function (Blueprint $table) {
            $table->id();

            $table->string('nombre', 120);
            $table->enum('tipo', ['general','obra'])->default('general');

            // Futuro: si el almacén pertenece a una obra (cuando una obra tenga bodega propia)
            $table->unsignedBigInteger('obra_id')->nullable();

            $table->boolean('activo')->default(true);

            $table->timestamps();

            // Índices útiles
            $table->index(['tipo', 'activo']);
            $table->index('obra_id');

            // FK opcional para futuro (si ya tienes tabla obras con id estándar)
            // Si tu tabla de obras no es 'obras' o no usa bigIncrements, lo dejamos sin FK por ahora.
            // $table->foreign('obra_id')->references('id')->on('obras')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('almacenes');
    }
};
