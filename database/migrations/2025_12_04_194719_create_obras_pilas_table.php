<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('obras_pilas', function (Blueprint $table) {
            // $table->id();
            $table->bigIncrements('id'); // o $table->id(); que es lo mismo

            $table->foreignId('obra_id')
                  ->constrained('obras')
                  ->cascadeOnDelete();

            $table->string('numero_pila', 50);     // PI-25-32, etc.

            $table->string('tipo', 50)->nullable(); // estructural, prueba, etc.

            $table->decimal('diametro_proyecto', 8, 2)->nullable();   // en cm, por ejemplo
            $table->decimal('profundidad_proyecto', 8, 2)->nullable(); // en m

            $table->string('ubicacion', 150)->nullable(); // Eje 3+20, etc.

            $table->boolean('activo')->default(true);

            $table->text('notas')->nullable();

            $table->timestamps();

            $table->unique(['obra_id', 'numero_pila']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('obras_pilas');
    }
};
