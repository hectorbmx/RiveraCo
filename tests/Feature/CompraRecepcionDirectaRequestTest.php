<?php

namespace Tests\Feature;

use App\Http\Requests\StoreCompraRecepcionDirectaRequest;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class CompraRecepcionDirectaRequestTest extends TestCase
{
    private function data(): array
    {
        return [
            'operacion_uuid' => (string) Str::uuid(),
            'fecha_recepcion' => now()->toDateString(),
            'receptor_nombre' => 'Receptor de prueba',
            'evidencias' => [UploadedFile::fake()->create('remision.pdf', 10, 'application/pdf')],
            'partidas' => [['orden_compra_detalle_id' => 1, 'cantidad_recibida' => 2.5]],
        ];
    }

    private function validate(array $data)
    {
        $request = new StoreCompraRecepcionDirectaRequest();

        return Validator::make($data, $request->rules(), $request->messages());
    }

    public function test_receipt_without_invoice_is_valid_when_delivery_is_evidenced(): void
    {
        $this->assertTrue($this->validate($this->data())->passes());
    }

    public function test_delivery_evidence_and_physical_recipient_are_required(): void
    {
        $data = $this->data();
        unset($data['evidencias'], $data['receptor_nombre']);
        $errors = $this->validate($data)->errors();
        $this->assertTrue($errors->has('evidencias'));
        $this->assertTrue($errors->has('receptor_nombre'));
    }

    public function test_destination_and_cost_cannot_be_supplied_by_the_client(): void
    {
        $data = $this->data() + ['obra_id' => 41, 'almacen_id' => 1, 'destino' => 'obra', 'costo_unitario' => 1];
        $data['partidas'][0]['precio_unitario'] = 1;
        $errors = $this->validate($data)->errors();
        foreach (['obra_id', 'almacen_id', 'destino', 'costo_unitario', 'partidas.0.precio_unitario'] as $field) {
            $this->assertTrue($errors->has($field), $field);
        }
    }

    public function test_duplicate_lines_invalid_quantities_and_future_dates_are_rejected(): void
    {
        $data = $this->data();
        $data['partidas'][] = $data['partidas'][0];
        $data['partidas'][0]['cantidad_recibida'] = 0;
        $data['fecha_recepcion'] = now()->addDay()->toDateString();
        $errors = $this->validate($data)->errors();
        $this->assertTrue($errors->has('partidas.0.orden_compra_detalle_id'));
        $this->assertTrue($errors->has('partidas.0.cantidad_recibida'));
        $this->assertTrue($errors->has('fecha_recepcion'));
    }

    public function test_unauthenticated_or_unauthorized_users_cannot_confirm(): void
    {
        $request = new StoreCompraRecepcionDirectaRequest();
        $this->assertFalse($request->authorize());
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('can')->with(StoreCompraRecepcionDirectaRequest::PERMISO_CONFIRMAR)->once()->andReturn(false);
        $request->setUserResolver(fn () => $user);
        $this->assertFalse($request->authorize());
    }

    public function test_explicit_permission_allows_confirmation(): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('can')->with(StoreCompraRecepcionDirectaRequest::PERMISO_CONFIRMAR)->once()->andReturn(true);
        $request = new StoreCompraRecepcionDirectaRequest();
        $request->setUserResolver(fn () => $user);
        $this->assertTrue($request->authorize());
    }
}
