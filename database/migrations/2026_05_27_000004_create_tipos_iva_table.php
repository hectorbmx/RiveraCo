<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_iva', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 80);
            $table->decimal('porcentaje', 5, 2);
            $table->boolean('activo')->default(true);
            $table->boolean('default')->default(false);
            $table->timestamps();

            $table->index(['activo', 'porcentaje']);
        });

        DB::table('tipos_iva')->insert([
            ['nombre' => 'Exento / 0%', 'porcentaje' => 0, 'activo' => true, 'default' => false, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'IVA 8%', 'porcentaje' => 8, 'activo' => true, 'default' => false, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'IVA 16%', 'porcentaje' => 16, 'activo' => true, 'default' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_iva');
    }
};
