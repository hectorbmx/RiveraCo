<?php

namespace Tests\Concerns;

use App\Models\OrdenCompra;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mockery;

trait BuildsCompraRecepcionDirectaDatabase
{
    protected function buildDirectReceiptDatabase(): void
    {
        if (! extension_loaded('pdo_sqlite')) $this->markTestSkipped('Requiere pdo_sqlite.');
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('users', function (Blueprint $t) { $t->id(); $t->string('name'); });
        Schema::create('obras', function (Blueprint $t) {
            $t->id(); $t->string('nombre'); $t->string('clave_obra')->nullable(); $t->integer('estatus_nuevo'); $t->softDeletes();
        });
        Schema::create('almacenes', function (Blueprint $t) {
            $t->id(); $t->string('nombre'); $t->string('codigo'); $t->boolean('activo'); $t->unsignedBigInteger('area_id');
        });
        Schema::create('productos', function (Blueprint $t) {
            $t->id(); $t->string('nombre'); $t->string('sku'); $t->string('unidad'); $t->boolean('activo');
            $t->string('unidad_base')->nullable(); $t->string('unidad_compra')->nullable(); $t->decimal('cantidad_por_unidad_compra', 18, 6)->nullable();
        });
        Schema::create('ordenes_compra', function (Blueprint $t) {
            $t->id(); $t->string('folio'); $t->unsignedBigInteger('obra_id'); $t->unsignedBigInteger('area_id');
            $t->unsignedBigInteger('proveedor_id')->nullable(); $t->string('estado'); $t->string('estado_recepcion')->default('pendiente');
            $t->string('moneda')->default('MXN'); $t->decimal('tipo_cambio', 18, 8)->default(1); $t->timestamps();
        });
        Schema::create('orden_compra_detalles', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('orden_compra_id'); $t->unsignedBigInteger('producto_id');
            $t->unsignedBigInteger('huentitan_salida_detalle_id')->nullable();
            $t->string('descripcion'); $t->string('unidad'); $t->decimal('cantidad', 15, 3);
            $t->decimal('cantidad_recibida', 15, 3)->default(0); $t->string('estado_recepcion')->default('pendiente');
            $t->decimal('precio_unitario', 18, 4); $t->decimal('importe', 18, 2)->nullable();
            $t->decimal('descuento_porcentaje', 8, 2)->nullable(); $t->decimal('descuento_importe', 18, 2)->nullable();
            $t->decimal('tipo_cambio', 18, 8)->nullable(); $t->timestamps();
        });
        Schema::create('obra_requisiciones', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('obra_id'); $t->string('estado'); $t->string('folio')->nullable();
            $t->unsignedBigInteger('solicitado_por')->nullable(); $t->unsignedBigInteger('autorizado_por')->nullable();
            $t->timestamp('autorizado_at')->nullable(); $t->timestamps();
        });
        Schema::create('obra_requisicion_detalles', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('obra_requisicion_id'); $t->string('item_type'); $t->unsignedBigInteger('item_id');
            $t->string('unidad'); $t->string('estado')->default('pendiente');
            $t->decimal('cantidad_autorizada', 15, 3)->nullable(); $t->decimal('cantidad_surtida', 15, 3)->default(0); $t->timestamps();
        });
        Schema::create('inventario_documentos', function (Blueprint $t) {
            $t->id(); $t->string('folio')->nullable()->unique(); $t->string('tipo'); $t->unsignedBigInteger('almacen_id');
            foreach (['obra_id', 'orden_compra_id', 'proveedor_id', 'creado_por', 'residente_id', 'documento_origen_id'] as $field) $t->unsignedBigInteger($field)->nullable();
            $t->string('estado'); $t->dateTime('fecha'); $t->string('motivo')->nullable(); $t->text('notas')->nullable();
            $t->timestamps(); $t->softDeletes();
        });
        Schema::create('inventario_documento_detalles', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('documento_id'); $t->unsignedBigInteger('producto_id');
            $t->unsignedBigInteger('orden_compra_id')->nullable(); $t->unsignedBigInteger('orden_compra_detalle_id')->nullable();
            $t->decimal('cantidad', 14, 3); $t->decimal('costo_unitario', 14, 4); $t->text('notas')->nullable(); $t->timestamps();
        });
        Schema::create('inventario_movimientos', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('almacen_id'); $t->unsignedBigInteger('producto_id'); $t->unsignedBigInteger('documento_id');
            $t->dateTime('fecha'); $t->string('tipo_movimiento'); $t->decimal('cantidad', 14, 3); $t->decimal('costo_unitario', 14, 4);
            $t->decimal('saldo_cantidad', 14, 3); $t->unsignedBigInteger('obra_id')->nullable(); $t->unsignedBigInteger('creado_por')->nullable(); $t->timestamps();
        });
        Schema::create('inventario_stock', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('almacen_id'); $t->unsignedBigInteger('producto_id');
            foreach (['stock_actual', 'stock_reservado', 'valor_total', 'costo_promedio'] as $field) $t->decimal($field, 18, 4)->default(0);
            $t->timestamps(); $t->unique(['almacen_id', 'producto_id']);
        });
        foreach (['2026_11_05_205300_create_compra_recepciones_directas_tables.php', '2026_11_05_205301_link_direct_receipts_to_inventory_movements.php',
            '2026_11_05_210800_add_delivery_destination_to_ordenes_compra.php', '2026_11_05_220000_add_base_quantity_and_exact_cost_to_direct_receipts.php',
            '2026_11_05_221800_add_request_hash_to_direct_receipts.php',
            '2026_11_06_000100_link_direct_receipt_lines_to_inventory_costs.php',
            '2026_11_06_010000_link_purchase_lines_to_obra_requisiciones.php'] as $file) {
            (require base_path('database/migrations/'.$file))->up();
        }
        DB::table('users')->insert(['id' => 1, 'name' => 'Compras de prueba']);
        DB::table('obras')->insert(['id' => 41, 'nombre' => 'Obra de prueba', 'estatus_nuevo' => 2]);
        DB::table('almacenes')->insert(['id' => 1, 'nombre' => 'Giralda', 'codigo' => 'GL', 'activo' => true, 'area_id' => 1]);
        DB::table('productos')->insert(['id' => 1, 'nombre' => 'Material', 'sku' => 'GL-1', 'unidad' => 'PZA', 'activo' => true]);
        DB::table('ordenes_compra')->insert(['id' => 1, 'folio' => 'OC-PRUEBA', 'obra_id' => 41, 'area_id' => 1,
            'estado' => 'AUTORIZADA', 'modalidad_entrega' => 'obra_directa', 'almacen_destino_id' => 1]);
        DB::table('orden_compra_detalles')->insert(['id' => 1, 'orden_compra_id' => 1, 'producto_id' => 1,
            'descripcion' => 'Material', 'unidad' => 'PZA', 'cantidad' => 10, 'precio_unitario' => 150, 'importe' => 1500]);
        DB::table('inventario_stock')->insert(['almacen_id' => 1, 'producto_id' => 1, 'stock_actual' => 10,
            'stock_reservado' => 2, 'valor_total' => 1000, 'costo_promedio' => 100]);
        Storage::fake('local');
    }

    protected function buyer(): User
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->id = 1;
        $user->shouldReceive('can')->andReturn(true);
        return $user;
    }

    protected function receiptInput(float $quantity = 4): array
    {
        return ['operacion_uuid' => (string) Str::uuid(), 'fecha_recepcion' => now()->toDateString(),
            'receptor_nombre' => 'Responsable de obra', 'evidencias' => [UploadedFile::fake()->create('remision.pdf', 10, 'application/pdf')],
            'partidas' => [['orden_compra_detalle_id' => 1, 'cantidad_recibida' => $quantity]]];
    }
}
