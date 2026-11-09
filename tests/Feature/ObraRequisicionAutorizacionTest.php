<?php
namespace Tests\Feature;

use App\Models\User;
use App\Models\OrdenCompra;
use App\Services\Requisiciones\AutorizarRequisicionService;
use App\Services\Compras\CompraRequisicionSaldoService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\Concerns\BuildsCompraRecepcionDirectaDatabase;
use Tests\TestCase;

class ObraRequisicionAutorizacionTest extends TestCase
{
    use BuildsCompraRecepcionDirectaDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildDirectReceiptDatabase();
        Schema::table('obra_requisicion_detalles', function (Blueprint $t) { $t->decimal('cantidad_solicitada', 12, 3)->default(0); });
        (require base_path('database/migrations/2026_11_09_150000_create_obra_requisicion_autorizaciones.php'))->up();
        DB::table('users')->insert([['id' => 2, 'name' => 'Revisor'], ['id' => 3, 'name' => 'Segundo revisor']]);
        DB::table('obra_requisiciones')->insert(['id' => 1, 'obra_id' => 41, 'estado' => 'pendiente_revision', 'solicitado_por' => 1]);
        DB::table('obra_requisicion_detalles')->insert([
            ['id' => 1, 'obra_requisicion_id' => 1, 'item_type' => 'producto', 'item_id' => 1, 'unidad' => 'PZA', 'cantidad_solicitada' => 10],
            ['id' => 2, 'obra_requisicion_id' => 1, 'item_type' => 'producto', 'item_id' => 1, 'unidad' => 'PZA', 'cantidad_solicitada' => 4],
        ]);
    }

    private function revisor(int $id): User
    {
        $user = Mockery::mock(User::class)->makePartial(); $user->id = $id;
        $user->shouldReceive('can')->with('requisiciones.autorizar.access')->andReturn(true);
        return $user;
    }

    public function test_partial_and_later_authorization_keep_audit_and_do_not_move_inventory(): void
    {
        $service = app(AutorizarRequisicionService::class);
        $service->autorizar(41, 1, $this->revisor(2), [1 => '3.000', 2 => '0']);
        $service->autorizar(41, 1, $this->revisor(3), [1 => '10', 2 => '2']);
        $this->assertEquals(10, DB::table('obra_requisicion_detalles')->where('id', 1)->value('cantidad_autorizada'));
        $this->assertEquals(2, DB::table('obra_requisicion_detalles')->where('id', 2)->value('cantidad_autorizada'));
        $this->assertEquals(2, DB::table('obra_requisiciones')->value('autorizado_por'));
        $this->assertEquals('autorizada', DB::table('obra_requisiciones')->value('estado'));
        $this->assertEquals(2, DB::table('obra_requisicion_autorizaciones')->count());
        $this->assertEquals(3, DB::table('obra_requisicion_autorizacion_detalles')->count());
        $this->assertEquals(0, DB::table('inventario_movimientos')->count());
        $this->assertEquals(10, DB::table('inventario_stock')->value('stock_actual'));
        DB::table('orden_compra_detalles')->where('id', 1)->update(['obra_requisicion_detalle_id' => 1]);
        DB::transaction(fn () => app(CompraRequisicionSaldoService::class)->validarVinculo(OrdenCompra::findOrFail(1), OrdenCompra::findOrFail(1)->detalles()->first()));
    }

    public function test_self_authorization_invalid_amounts_and_foreign_lines_do_not_change_data(): void
    {
        $service = app(AutorizarRequisicionService::class);
        foreach ([[$this->revisor(1), [1 => 1]], [$this->revisor(2), [1 => 11]], [$this->revisor(2), [1 => 1, 99 => 1]], [$this->revisor(2), [1 => 0]], [$this->revisor(2), [1 => '1.0001']]] as [$user, $amounts]) {
            try { $service->autorizar(41, 1, $user, $amounts); $this->fail('Debe rechazar la autorización.'); }
            catch (ValidationException $e) { $this->assertNotEmpty($e->errors()); }
        }
        $this->assertEquals('pendiente_revision', DB::table('obra_requisiciones')->value('estado'));
        $this->assertEquals(0, DB::table('obra_requisicion_autorizaciones')->count());
        $service->autorizar(41, 1, $this->revisor(2), [1 => 4]);
        try { $service->autorizar(41, 1, $this->revisor(3), [1 => 3, 2 => 2]); $this->fail('No puede reducir lo autorizado.'); }
        catch (ValidationException $e) { $this->assertNotEmpty($e->errors()); }
        $this->assertEquals(1, DB::table('obra_requisicion_autorizaciones')->count());
        $this->assertEquals(0, (float) DB::table('obra_requisicion_detalles')->where('id', 2)->value('cantidad_autorizada'));
    }
}
