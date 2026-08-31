<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reposicion_caja_chica_categorias', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 80)->unique();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->boolean('requiere_factura')->default(false);
            $table->boolean('requiere_xml')->default(false);
            $table->string('forma_pago_base', 30)->nullable();
            $table->boolean('activo')->default(true);
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();
        });

        DB::table('reposicion_caja_chica_categorias')->insert([
            [
                'codigo' => 'efectivo_factura',
                'nombre' => 'Con efectivo y factura',
                'descripcion' => 'Gasto pagado en efectivo con comprobante fiscal.',
                'requiere_factura' => true,
                'requiere_xml' => true,
                'forma_pago_base' => 'efectivo',
                'activo' => true,
                'orden' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => 'tarjeta_factura',
                'nombre' => 'Con tarjeta y factura',
                'descripcion' => 'Gasto pagado con tarjeta con comprobante fiscal.',
                'requiere_factura' => true,
                'requiere_xml' => true,
                'forma_pago_base' => 'tarjeta',
                'activo' => true,
                'orden' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => 'sin_factura_viaticos',
                'nombre' => 'Sin factura (viaticos)',
                'descripcion' => 'Gasto sin factura correspondiente a viaticos.',
                'requiere_factura' => false,
                'requiere_xml' => false,
                'forma_pago_base' => null,
                'activo' => true,
                'orden' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => 'sin_factura_reembolso',
                'nombre' => 'Sin factura (reembolso)',
                'descripcion' => 'Gasto sin factura para reembolso.',
                'requiere_factura' => false,
                'requiere_xml' => false,
                'forma_pago_base' => null,
                'activo' => true,
                'orden' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('reposicion_caja_chica_categorias');
    }
};
