<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('catalogo_segmentos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100)->unique();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::table('productos', function (Blueprint $table) {
            // Destino de uso (segmento o maquina)
            $table->string('uso_type', 20)->nullable()->after('tipo_inventario'); // 'segmento'|'maquina'
            $table->unsignedBigInteger('uso_id')->nullable()->after('uso_type');
            $table->string('uso_label', 120)->nullable()->after('uso_id'); // texto original del Excel (opcional)

            $table->index(['uso_type','uso_id'], 'productos_uso_idx');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropIndex('productos_uso_idx');
            $table->dropColumn(['uso_type','uso_id','uso_label']);
        });

        Schema::dropIfExists('catalogo_segmentos');
    }
};
