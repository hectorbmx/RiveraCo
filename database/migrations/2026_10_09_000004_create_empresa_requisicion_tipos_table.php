<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empresa_requisicion_tipos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 40)->unique();
            $table->string('numero_catalogo', 2)->unique();
            $table->string('nombre', 120);
            $table->string('descripcion', 255)->nullable();
            $table->boolean('activo')->default(true);
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();

            $table->index(['activo', 'orden'], 'empresa_req_tipos_activo_orden_index');
        });

        $now = now();
        $tipos = [
            ['codigo' => 'personal', 'numero_catalogo' => '01', 'nombre' => 'Personal / empleados', 'descripcion' => 'Solicitud de personal o empleados para obra.', 'orden' => 10],
            ['codigo' => 'maquinaria', 'numero_catalogo' => '02', 'nombre' => 'Maquinaria', 'descripcion' => 'Solicitud de maquinaria para obra.', 'orden' => 20],
            ['codigo' => 'herramienta', 'numero_catalogo' => '03', 'nombre' => 'Herramienta', 'descripcion' => 'Solicitud de herramientas para obra.', 'orden' => 30],
            ['codigo' => 'material', 'numero_catalogo' => '04', 'nombre' => 'Material / insumo', 'descripcion' => 'Solicitud de materiales o insumos para obra.', 'orden' => 40],
            ['codigo' => 'otro', 'numero_catalogo' => '05', 'nombre' => 'Otro', 'descripcion' => 'Solicitud general no clasificada.', 'orden' => 50],
        ];

        foreach ($tipos as $tipo) {
            DB::table('empresa_requisicion_tipos')->updateOrInsert(
                ['codigo' => $tipo['codigo']],
                array_merge($tipo, [
                    'activo' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('empresa_requisicion_tipos');
    }
};
