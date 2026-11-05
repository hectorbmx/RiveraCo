<?php

namespace Tests\Feature;

use App\Http\Controllers\ReposicionCajaChicaController;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ReposicionCajaChicaOrigenTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('Requiere pdo_sqlite.');
        }
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('reposicion_caja_chica_gastos', function (Blueprint $t) {
            $t->id();
            foreach (['origen_captura', 'destino', 'estado_autorizacion', 'concepto'] as $field) {
                $t->string($field);
            }
            foreach (['obra_id', 'almacen_id', 'categoria_id', 'subcategoria_id', 'maquina_id', 'vehiculo_id', 'solicitado_por'] as $field) {
                $t->unsignedBigInteger($field)->nullable();
            }
            $t->unsignedBigInteger('relacion_id')->nullable();
            $t->date('fecha_gasto');
            $t->decimal('importe_registrado');
            $t->decimal('importe_autorizado')->nullable();
            $t->timestamps();
        });
        foreach (['obras', 'almacenes'] as $table) {
            Schema::create($table, function (Blueprint $t) {
                $t->id(); $t->string('nombre'); $t->string('clave_obra')->nullable(); $t->softDeletes();
            });
        }
        DB::table('obras')->insert([
            ['id' => 41, 'nombre' => 'Obra 41'], ['id' => 42, 'nombre' => 'Obra 42'],
        ]);
        DB::table('almacenes')->insert(['id' => 1, 'nombre' => 'Giralda']);
        Schema::create('documento_firmantes', function (Blueprint $t) {
            $t->id(); $t->string('documento'); $t->string('ambito');
            $t->string('campo'); $t->boolean('activo'); $t->unsignedBigInteger('user_id');
        });
        Schema::create('documento_firma_definiciones', function (Blueprint $t) {
            $t->id(); $t->string('documento'); $t->string('documento_label');
            $t->string('ambito'); $t->string('ambito_label'); $t->integer('orden'); $t->boolean('activo');
        });
        Schema::create('reposicion_caja_chica_relaciones', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('responsable_user_id')->nullable();
            $t->unsignedBigInteger('almacen_id')->nullable(); $t->timestamps();
        });
        foreach ([
            [1, 'almacen', 'obra', 41, null],
            [2, 'almacen', 'almacen', null, 1],
            [3, 'obra_residente', 'obra', 41, null],
            [4, 'obra_residente', 'obra', 42, null],
        ] as [$id, $origen, $destino, $obra, $almacen]) {
            DB::table('reposicion_caja_chica_gastos')->insert([
                'id' => $id, 'origen_captura' => $origen, 'destino' => $destino,
                'obra_id' => $obra, 'almacen_id' => $almacen, 'estado_autorizacion' => 'pendiente',
                'concepto' => 'GASTO_ORIGEN_'.$id, 'importe_registrado' => $id * 100,
                'fecha_gasto' => '2026-11-05', 'created_at' => '2026-11-05 12:00:00',
            ]);
        }
    }

    private function requestFor(array $params = []): Request
    {
        return Request::create('/', 'GET', $params + ['fecha_inicio' => '2026-11-02', 'fecha_fin' => '2026-11-08']);
    }

    private function idsFor(array $params): array
    {
        $method = new ReflectionMethod(ReposicionCajaChicaController::class, 'gastosReporteQuery');
        $query = $method->invoke(app(ReposicionCajaChicaController::class), $this->requestFor($params),
            Carbon::parse('2026-11-02'), Carbon::parse('2026-11-08')->endOfDay());

        return $query->orderBy('id')->pluck('id')->all();
    }

    public function test_general_and_giralda_reports_exclude_resident_expenses(): void
    {
        $this->assertSame([1, 2], $this->idsFor([]));
        $this->assertSame([1, 2], $this->idsFor(['ambito' => 'reposicion_gastos_almacen']));
        $this->assertSame([1, 2], $this->idsFor(['ambito' => 'giralda']));
        $this->assertSame([1], $this->idsFor(['destino' => 'obra', 'obra_id' => 41]));
    }

    public function test_resident_expenses_are_limited_to_one_work(): void
    {
        $this->assertSame([3], $this->idsFor(['origen_captura' => 'obra_residente', 'obra_id' => 41]));
        $this->assertSame([4], $this->idsFor(['ambito' => 'obra', 'obra_id' => 42]));
        $this->assertSame([3], $this->idsFor([
            'origen_captura' => 'obra_residente', 'obra_id' => 41, 'destino' => 'almacen', 'almacen_id' => 1,
        ]));
    }

    public function test_resident_report_requires_a_work(): void
    {
        try {
            $this->idsFor(['origen_captura' => 'obra_residente']);
            $this->fail('No debe permitir un reporte general de gastos del residente.');
        } catch (HttpException $e) {
            $this->assertSame(422, $e->getStatusCode());
        }
    }

    public function test_print_formats_keep_sources_separate(): void
    {
        $controller = app(ReposicionCajaChicaController::class);
        foreach (['imprimirReporte', 'imprimirFormatoAdministrativo'] as $action) {
            $general = $controller->$action($this->requestFor())->getData();
            $workView = $controller->$action($this->requestFor(['origen_captura' => 'obra_residente', 'obra_id' => 41]));
            $work = $workView->getData();
            $this->assertSame([1, 2], $general['gastos']->pluck('id')->sort()->values()->all());
            $this->assertSame([3], $work['gastos']->pluck('id')->all());
            $stats = (new ReflectionMethod($controller, 'stats'))->invoke($controller, $this->requestFor(),
                Carbon::parse('2026-11-02'), Carbon::parse('2026-11-08')->endOfDay());
            $this->assertSame(300.0, (float) $stats['registrado']);
            $this->assertSame($action === 'imprimirReporte'
                ? 'reposicion-caja-chica.reporte-imprimir'
                : 'reposicion-caja-chica.reporte-formato-administrativo', $workView->name());
        }
    }

    public function test_excel_and_office_review_keep_sources_separate(): void
    {
        $controller = app(ReposicionCajaChicaController::class);
        foreach ([['params' => [], 'ids' => [1, 2]], ['params' => ['origen_captura' => 'obra_residente', 'obra_id' => 41], 'ids' => [3]]] as $case) {
            $review = $controller->revision($this->requestFor($case['params']))->getData();
            $this->assertSame($case['ids'], $review['gastos']->pluck('id')->sort()->values()->all());
            $response = $controller->exportarExcel($this->requestFor($case['params']));
            ob_start();
            try { $response->sendContent(); $html = ob_get_contents(); } finally { ob_end_clean(); }
            foreach ([1, 2, 3, 4] as $id) {
                if (in_array($id, $case['ids'], true)) {
                    $this->assertStringContainsString('GASTO_ORIGEN_'.$id, $html);
                } else {
                    $this->assertStringNotContainsString('GASTO_ORIGEN_'.$id, $html);
                }
            }
        }
    }

    public function test_warehouse_relations_cannot_print_resident_expenses(): void
    {
        DB::table('reposicion_caja_chica_relaciones')->insert([['id' => 1], ['id' => 2]]);
        DB::table('reposicion_caja_chica_gastos')->where('id', 1)->update(['relacion_id' => 1]);
        DB::table('reposicion_caja_chica_gastos')->where('id', 3)->update(['relacion_id' => 2]);
        $controller = app(ReposicionCajaChicaController::class);
        $this->assertSame([1], $controller->relaciones()->getData()['relaciones']->pluck('id')->all());

        try {
            $controller->imprimirRelacion(\App\Models\ReposicionCajaChicaRelacion::findOrFail(2));
            $this->fail('No debe imprimir gastos de obra dentro de una relación de almacén.');
        } catch (HttpException $e) {
            $this->assertSame(422, $e->getStatusCode());
        }
    }
}
