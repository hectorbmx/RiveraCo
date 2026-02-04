<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {

            // Clasificación de inventario
            $table->enum('tipo_inventario', ['consumible','herramienta'])
                ->default('consumible')
                ->after('nombre'); // ajusta si tu tabla no tiene 'nombre'

            // Alertas / mínimos (decimales)
            $table->decimal('stock_minimo', 14, 3)
                ->default(0)
                ->after('tipo_inventario');

            $table->decimal('punto_reorden', 14, 3)
                ->default(0)
                ->after('stock_minimo');

            // Opcional (si te sirve apagar alertas por producto)
            // $table->boolean('alerta_reorden_activa')->default(true)->after('punto_reorden');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            // OJO: primero elimina columnas agregadas
            $table->dropColumn(['tipo_inventario','stock_minimo','punto_reorden']);
            // Si agregaste alerta_reorden_activa:
            // $table->dropColumn(['alerta_reorden_activa']);
        });
    }
};
