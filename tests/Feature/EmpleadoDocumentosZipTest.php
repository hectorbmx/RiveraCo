<?php

namespace Tests\Feature;

use App\Http\Controllers\EmpleadoDocumentoController;
use App\Models\Empleado;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmpleadoDocumentosZipTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (! extension_loaded('pdo_sqlite') || ! class_exists(\ZipArchive::class)) $this->markTestSkipped('Requiere SQLite y ZIP.');
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite'); Storage::fake('public');
        Schema::create('empleado_documentos', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('empleado_id'); $table->string('tipo_documento');
            $table->string('nombre_documento')->nullable(); $table->string('archivo_path')->nullable(); $table->softDeletes();
        });
        DB::table('empleado_documentos')->insert([
            ['id' => 1, 'empleado_id' => 12, 'tipo_documento' => 'INE', 'nombre_documento' => 'Identificación', 'archivo_path' => 'empleados/12/documentos/a.pdf'],
            ['id' => 2, 'empleado_id' => 12, 'tipo_documento' => 'INE', 'nombre_documento' => 'Identificación', 'archivo_path' => 'empleados/12/documentos/b.png'],
            ['id' => 3, 'empleado_id' => 99, 'tipo_documento' => 'OTRO', 'nombre_documento' => null, 'archivo_path' => 'empleados/99/documentos/c.pdf'],
        ]);
        Storage::disk('public')->put('empleados/12/documentos/a.pdf', 'documento A');
        Storage::disk('public')->put('empleados/12/documentos/b.png', 'documento B');
    }

    public function test_download_contains_only_selected_employee_files_and_deletes_temporary_zip(): void
    {
        $empleado = new Empleado(); $empleado->id_Empleado = 12;
        $response = app(EmpleadoDocumentoController::class)->descargarSeleccion(Request::create('/', 'POST', ['documentos' => [1, 2]]), $empleado);
        $path = $response->getFile()->getPathname();
        $zip = new \ZipArchive(); $this->assertTrue($zip->open($path));
        $this->assertSame(2, $zip->numFiles);
        $this->assertSame('documento A', $zip->getFromName('identificacion_1.pdf'));
        $this->assertSame('documento B', $zip->getFromName('identificacion_2.png'));
        $zip->close();
        ob_start(); $response->sendContent(); ob_end_clean();
        $this->assertFileDoesNotExist($path);
        $this->assertContains('permission:empleados.access', Route::getRoutes()->getByName('empleados.documentos.descargar-seleccion')->gatherMiddleware());
    }

    public function test_another_employees_document_is_rejected(): void
    {
        $empleado = new Empleado(); $empleado->id_Empleado = 12;
        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
        app(EmpleadoDocumentoController::class)->descargarSeleccion(Request::create('/', 'POST', ['documentos' => [1, 3]]), $empleado);
    }

    public function test_missing_file_rejects_the_whole_download(): void
    {
        Storage::disk('public')->delete('empleados/12/documentos/b.png');
        $empleado = new Empleado(); $empleado->id_Empleado = 12;
        $response = app(EmpleadoDocumentoController::class)->descargarSeleccion(Request::create('/', 'POST', ['documentos' => [1, 2]]), $empleado);
        $this->assertTrue($response->getSession()->get('errors')->has('documentos'));
    }
}
