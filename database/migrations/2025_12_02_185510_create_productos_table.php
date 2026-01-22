<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id();

            // ID del sistema viejo (en legacy es prod_id VARCHAR(50))
            $table->string('legacy_prod_id', 50)->nullable()->index();

            // Datos base
            $table->string('nombre', 255);          // viene de "concepto" normalmente
            $table->string('descripcion', 500)->nullable();

            // Código interno opcional (si en el futuro quieres SKU/código de barras)
            $table->string('sku', 100)->nullable()->unique();

            // Unidad de medida (pieza, m, kg, servicio, hora, etc.)
            $table->string('unidad', 50)->nullable();

            // Para distinguir más adelante
            // PRODUCTO / SERVICIO (por ahora puedes dejarlo siempre en PRODUCTO si quieres)
            $table->string('tipo', 20)->default('PRODUCTO');

            // Para activar/desactivar productos sin borrarlos
            $table->boolean('activo')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
