<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ObraDevolucionSchemaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (! extension_loaded('pdo_sqlite')) $this->markTestSkipped('Requiere pdo_sqlite.');
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']); DB::purge('sqlite');
        foreach (['users', 'obras', 'productos', 'almacenes', 'compra_recepcion_directa_detalles', 'inventario_movimientos', 'huentitan_salida_detalles', 'inventario_documento_detalles'] as $name) {
            Schema::create($name, fn (Blueprint $t) => $t->id()); DB::table($name)->insert(['id' => 1]);
        }
        (require base_path('database/migrations/2026_11_06_020000_create_obra_devoluciones_tables.php'))->up();
        DB::table('obra_devoluciones')->insert(['id' => 1, 'obra_id' => 1]);
    }

    private function line(array $origin): array
    {
        return array_merge(['devolucion_id' => 1, 'producto_id' => 1, 'descripcion' => 'Material', 'unidad' => 'PZA',
            'cantidad_solicitada' => 2, 'cantidad_entregada_snapshot' => 5, 'costo_unitario_mxn' => 10,
            'importe_origen_mxn' => 50, 'condicion_declarada' => 'bueno'], $origin);
    }

    public function test_each_supported_origin_is_persisted_with_its_foreign_key(): void
    {
        foreach (['recepcion_directa_detalle_id', 'inventario_movimiento_id', 'huentitan_salida_detalle_id'] as $field) {
            DB::table('obra_devolucion_detalles')->insert($this->line([$field => 1]));
        }
        $this->assertSame(3, DB::table('obra_devolucion_detalles')->count());
        $this->expectException(QueryException::class);
        DB::table('obra_devolucion_detalles')->insert($this->line(['inventario_movimiento_id' => 999]));
    }

    public function test_no_origin_and_multiple_origins_are_rejected_on_insert_and_update(): void
    {
        foreach ([[], ['inventario_movimiento_id' => 1, 'huentitan_salida_detalle_id' => 1]] as $origin) {
            try { DB::table('obra_devolucion_detalles')->insert($this->line($origin)); $this->fail('Debe exigir un origen único.'); }
            catch (QueryException $e) { $this->assertNotEmpty($e->getMessage()); }
        }
        $id = DB::table('obra_devolucion_detalles')->insertGetId($this->line(['inventario_movimiento_id' => 1]));
        $this->expectException(QueryException::class);
        DB::table('obra_devolucion_detalles')->where('id', $id)->update(['huentitan_salida_detalle_id' => 1]);
    }

    public function test_partial_receipts_preserve_individual_conditions_and_unique_operation(): void
    {
        $line = DB::table('obra_devolucion_detalles')->insertGetId($this->line(['recepcion_directa_detalle_id' => 1]));
        $receipt = DB::table('obra_devolucion_recepciones')->insertGetId(['devolucion_id' => 1, 'operacion_uuid' => '11111111-1111-4111-8111-111111111111', 'almacen_id' => 1, 'fecha_recepcion' => '2026-11-06']);
        foreach (['bueno', 'pendiente_evaluacion'] as $state) DB::table('obra_devolucion_recepcion_detalles')->insert([
            'recepcion_id' => $receipt, 'devolucion_detalle_id' => $line, 'cantidad_recibida' => 1,
            'condicion_recibida' => $state, 'estado_evaluacion' => $state === 'bueno' ? null : $state, 'costo_unitario_mxn' => 10,
        ]);
        $this->assertSame(2, DB::table('obra_devolucion_recepcion_detalles')->count());
        $this->expectException(QueryException::class);
        DB::table('obra_devolucion_recepciones')->insert(['devolucion_id' => 1, 'operacion_uuid' => '11111111-1111-4111-8111-111111111111', 'almacen_id' => 1, 'fecha_recepcion' => '2026-11-06']);
    }
}
