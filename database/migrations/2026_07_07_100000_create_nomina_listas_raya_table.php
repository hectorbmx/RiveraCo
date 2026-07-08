<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('nomina_listas_raya', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->string('tipo', 30)->default('operativa');
            $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete();
            $table->foreignId('obra_id')->nullable()->constrained('obras')->nullOnDelete();
            $table->unsignedBigInteger('almacen_id')->nullable();
            $table->boolean('activo')->default(true);
            $table->boolean('es_automatica')->default(false);
            $table->unsignedInteger('orden')->default(100);
            $table->timestamps();

            $table->unique(['tipo', 'obra_id'], 'nomina_listas_raya_tipo_obra_unique');
            $table->index(['tipo', 'activo']);
            $table->index('area_id');
            $table->index('almacen_id');
        });

        $now = now();
        DB::table('nomina_listas_raya')->insert([
            ['nombre' => 'Oficina', 'tipo' => 'oficina', 'activo' => true, 'es_automatica' => false, 'orden' => 10, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Almacen Giralda', 'tipo' => 'almacen', 'activo' => true, 'es_automatica' => false, 'orden' => 20, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Almacen Huentitan', 'tipo' => 'almacen', 'activo' => true, 'es_automatica' => false, 'orden' => 30, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Pilas', 'tipo' => 'operativa', 'activo' => true, 'es_automatica' => false, 'orden' => 40, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Pozos', 'tipo' => 'operativa', 'activo' => true, 'es_automatica' => false, 'orden' => 50, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'Sin clasificar', 'tipo' => 'operativa', 'activo' => true, 'es_automatica' => false, 'orden' => 999, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('nomina_listas_raya');
    }
};
