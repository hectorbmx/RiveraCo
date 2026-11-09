<?php

namespace Tests\Feature;

use App\Http\Controllers\Sat\SatFacturacionController;
use App\Models\Cliente;
use App\Models\SatFacturaBorrador;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class SatFacturacionRelacionTest extends TestCase
{
    private const UUIDS = '11111111-1111-4111-8111-111111111111, 22222222-2222-4222-8222-222222222222';

    protected function setUp(): void
    {
        parent::setUp();
        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('Requiere pdo_sqlite.');
        }
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('sat_factura_borradores', function (Blueprint $table) {
            $table->id();
            foreach (['user_id', 'sat_empresa_id', 'cliente_id', 'obra_id', 'obra_factura_borrador_id', 'sat_factura_id'] as $column) {
                $table->unsignedBigInteger($column)->nullable();
            }
            $table->string('titulo');
            $table->text('payload');
            $table->string('estado');
            $table->timestamps();
        });
    }

    public function test_draft_saves_and_reopens_relationship_and_uuids(): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->id = 1;
        $user->shouldReceive('can')->with('sat.borradores.access')->andReturn(true);
        Auth::setUser($user);
        $request = Request::create('/', 'POST', ['usar_relacion' => '1', 'relacion_tipo' => '07', 'relacion_uuids' => self::UUIDS]);
        $controller = app(SatFacturacionController::class);
        $controller->storeBorrador($request);

        $draft = SatFacturaBorrador::firstOrFail();
        $this->assertSame('07', $draft->payload['relacion_tipo']);
        $this->assertSame(self::UUIDS, $draft->payload['relacion_uuids']);
        $prefill = $this->invoke($controller, 'prefillFromCfdiBorrador', [$draft]);
        $html = view('sat.facturacion._tipo_relacion', ['prefill' => $prefill])->render();
        $this->assertMatchesRegularExpression('/value="07"\s+selected/', $html);
        $this->assertSame(self::UUIDS, $prefill['relacion_uuids']);

        request()->setLaravelSession(session()->driver());
        session()->flashInput(['relacion_tipo' => '02']);
        $html = view('sat.facturacion._tipo_relacion', ['prefill' => $prefill])->render();
        $this->assertMatchesRegularExpression('/value="02"\s+selected/', $html);
        $this->assertDoesNotMatchRegularExpression('/value="07"\s+selected/', $html);
    }

    public function test_preview_and_shared_invoice_builder_use_facturapi_related_documents(): void
    {
        $controller = app(SatFacturacionController::class);
        $input = ['usar_relacion' => '1', 'relacion_tipo' => '07', 'relacion_uuids' => self::UUIDS];
        $request = Request::create('/', 'POST', $input);
        $data = $input + [
            'tipo_iva' => '0.16', 'metodo_pago' => 'PUE', 'uso_cfdi' => 'G03',
            'conceptos' => [['descripcion' => 'Servicio', 'clave_producto_servicio' => '84111506', 'clave_unidad' => 'ACT', 'unidad' => 'Actividad', 'cantidad' => 1, 'precio_unitario' => 100]],
        ];
        $cliente = new Cliente(['razon_social' => 'Cliente', 'regimen_fiscal' => '601', 'codigo_postal' => '44100']);
        $payload = $this->invoke($controller, 'buildFacturapiPreviewPayload', [$request, $data, $cliente, 'ABC010101AAA']);
        $expected = [['relationship' => '07', 'documents' => explode(', ', self::UUIDS)]];
        $this->assertSame($expected, $payload['related_documents']);
        $this->assertArrayNotHasKey('related', $payload);

        $invoicePayload = ['customer' => 'cliente'];
        $method = new \ReflectionMethod($controller, 'agregarDocumentosRelacionados');
        $method->invokeArgs($controller, [&$invoicePayload, $request, $data]);
        $this->assertSame($expected, $invoicePayload['related_documents']);

        $unrelated = [];
        $method->invokeArgs($controller, [&$unrelated, Request::create('/', 'POST'), $data]);
        $this->assertArrayNotHasKey('related_documents', $unrelated);
    }

    private function invoke(object $object, string $method, array $arguments): mixed
    {
        return (new \ReflectionMethod($object, $method))->invokeArgs($object, $arguments);
    }
}
