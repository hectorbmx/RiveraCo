<?php

namespace Tests\Feature;

use App\Models\CompraRecepcionDirecta;
use App\Models\InventarioDocumento;
use App\Models\OrdenCompra;
use Database\Seeders\CompraRecepcionDirectaPermisosSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class CompraRecepcionDirectaSchemaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('Requiere pdo_sqlite.');
        }
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        foreach (['ordenes_compra', 'obras', 'almacenes', 'users', 'productos', 'orden_compra_detalles', 'obra_requisicion_detalles'] as $name) {
            Schema::create($name, fn (Blueprint $t) => $t->id());
            DB::table($name)->insert(['id' => 1]);
        }
        Schema::create('inventario_documentos', function (Blueprint $t) {
            $t->id(); $t->string('tipo'); $t->timestamps(); $t->softDeletes();
        });
        Schema::create('inventario_movimientos', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('documento_id'); $t->timestamps();
        });
        $migration = require base_path('database/migrations/2026_11_05_205300_create_compra_recepciones_directas_tables.php');
        $migration->up();
        (require base_path('database/migrations/2026_11_05_205301_link_direct_receipts_to_inventory_movements.php'))->up();
    }

    private function receipt(): CompraRecepcionDirecta
    {
        return CompraRecepcionDirecta::create([
            'operacion_uuid' => (string) Str::uuid(), 'orden_compra_id' => 1,
            'obra_id' => 1, 'almacen_id' => 1, 'fecha_recepcion' => '2026-11-05',
            'receptor_nombre' => 'Receptor de prueba', 'moneda' => 'MXN', 'confirmado_por' => 1,
        ]);
    }

    public function test_receipt_links_order_documents_lines_and_evidence(): void
    {
        $receipt = $this->receipt();
        $in = $receipt->documentos()->create(['tipo' => 'entrada']);
        $out = $receipt->documentos()->create(['tipo' => 'salida']);
        $receipt->update(['entrada_documento_id' => $in->id, 'salida_documento_id' => $out->id]);
        $movement = $receipt->movimientos()->create(['documento_id' => $out->id]);
        $line = $receipt->detalles()->create([
            'orden_compra_detalle_id' => 1, 'producto_id' => 1, 'descripcion' => 'Material',
            'cantidad_ordenada' => 10, 'cantidad_recibida' => 6, 'precio_unitario_oc' => 100,
            'descuento_oc' => 100, 'importe_neto_oc' => 900, 'costo_unitario_oc' => 90,
            'costo_unitario_mxn' => 90, 'importe_oc' => 540, 'importe_mxn' => 540,
        ]);
        $file = $receipt->archivos()->create([
            'path' => 'recepciones/prueba/remision.pdf', 'nombre_original' => 'remision.pdf',
            'mime_type' => 'application/pdf', 'size_bytes' => 10, 'hash_sha256' => str_repeat('a', 64),
        ]);
        $this->assertSame($receipt->id, OrdenCompra::findOrFail(1)->recepcionesDirectas->sole()->id);
        $this->assertSame($receipt->id, InventarioDocumento::findOrFail($in->id)->recepcionDirecta->id);
        $this->assertSame($out->id, $receipt->fresh()->salida->id);
        $this->assertSame($receipt->id, $line->recepcion->id);
        $this->assertSame($receipt->id, $movement->recepcionDirecta->id);
        $this->assertSame($receipt->id, $file->recepcion->id);
        $this->assertSame('90.00000000', $line->fresh()->costo_unitario_mxn);
        $this->assertSame(CompraRecepcionDirecta::ESTADO_BORRADOR, $receipt->fresh()->estado);
    }

    public function test_operation_uuid_is_unique_in_the_database(): void
    {
        $receipt = $this->receipt();
        $this->expectException(QueryException::class);
        $copy = $receipt->replicate();
        $copy->save();
    }

    public function test_permission_seeder_is_idempotent_and_does_not_change_assignments(): void
    {
        Schema::create('permissions', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('guard_name'); $t->timestamps();
            $t->unique(['name', 'guard_name']);
        });
        Schema::create('model_has_permissions', function (Blueprint $t) {
            $t->unsignedBigInteger('permission_id'); $t->string('model_type'); $t->unsignedBigInteger('model_id');
        });
        DB::table('permissions')->insert(['id' => 1, 'name' => 'existente.access', 'guard_name' => 'web']);
        DB::table('model_has_permissions')->insert(['permission_id' => 1, 'model_type' => 'App\\Models\\User', 'model_id' => 1]);
        $seeder = app(CompraRecepcionDirectaPermisosSeeder::class);
        $seeder->run();
        $seeder->run();
        $this->assertSame(4, DB::table('permissions')->count());
        $this->assertSame(1, DB::table('model_has_permissions')->count());
        $this->assertSame(1, (int) DB::table('model_has_permissions')->value('permission_id'));
    }
}
