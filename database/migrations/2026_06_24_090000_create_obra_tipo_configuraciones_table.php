<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('obra_tipo_configuraciones', function (Blueprint $table) {
            $table->id();
            $table->string('tipo_obra', 30)->unique();
            $table->string('label', 100);
            $table->string('prefijo', 10);
            $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        $defaults = [
            ['tipo_obra' => 'PILAS', 'label' => 'Pilas', 'prefijo' => 'PI'],
            ['tipo_obra' => 'POZOS', 'label' => 'Pozos', 'prefijo' => 'PO'],
        ];

        foreach ($defaults as $item) {
            $areaId = DB::table('areas')
                ->where(function ($query) use ($item) {
                    $query->where('codigo', $item['tipo_obra'])
                        ->orWhere('codigo', $item['prefijo'])
                        ->orWhere('nombre', 'like', '%' . $item['label'] . '%');
                })
                ->value('id');

            DB::table('obra_tipo_configuraciones')->insert([
                'tipo_obra' => $item['tipo_obra'],
                'label' => $item['label'],
                'prefijo' => $item['prefijo'],
                'area_id' => $areaId,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($areaId && Schema::hasColumn('obras', 'area_id')) {
                DB::table('obras')
                    ->where('tipo_obra', $item['tipo_obra'])
                    ->update(['area_id' => $areaId]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('obra_tipo_configuraciones');
    }
};
