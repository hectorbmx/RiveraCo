<?php

namespace App\Http\Controllers;

use App\Models\Almacen;
use App\Models\DocumentoFirmante;
use App\Models\Maquina;
use App\Models\Obra;
use App\Models\Proveedor;
use App\Models\ReposicionCajaChicaCategoria;
use App\Models\ReposicionCajaChicaGasto;
use App\Models\ReposicionCajaChicaGastoArchivo;
use App\Models\ReposicionCajaChicaRelacion;
use App\Models\ReposicionCajaChicaSubcategoria;
use App\Models\User;
use App\Models\Vehiculo;
use App\Services\Sat\CfdiXmlParserService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ReposicionCajaChicaController extends Controller
{
    public function index(Request $request)
    {
        [$fechaInicio, $fechaFin] = $this->resolverRangoSemana($request);

        $gastos = $this->gastosReporteQuery($request, $fechaInicio, $fechaFin)
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $categorias = $this->categoriasActivas();
        $stats = $this->stats($request, $fechaInicio, $fechaFin);
        $destinosImpresion = $this->destinosImpresionReposicion($request, $fechaInicio, $fechaFin);
        $ambitoFirma = $this->resolverAmbitoFirmaReposicionDisponible($request, $gastos->getCollection());
        $puedeElegirAmbitoFirma = $this->puedeElegirAmbitoFirmaReposicion($request);

        $semanaAnteriorInicio = $fechaInicio->copy()->subWeek()->toDateString();
        $semanaAnteriorFin = $fechaFin->copy()->subWeek()->toDateString();
        $semanaSiguienteInicio = $fechaInicio->copy()->addWeek()->toDateString();
        $semanaSiguienteFin = $fechaFin->copy()->addWeek()->toDateString();

        return view('reposicion-caja-chica.index', compact(
            'gastos',
            'categorias',
            'stats',
            'fechaInicio',
            'fechaFin',
            'semanaAnteriorInicio',
            'semanaAnteriorFin',
            'semanaSiguienteInicio',
            'semanaSiguienteFin',
            'ambitoFirma',
            'puedeElegirAmbitoFirma',
            'destinosImpresion'
        ));
    }

    public function create()
    {
        $categorias = $this->categoriasActivas();
        $subcategorias = ReposicionCajaChicaSubcategoria::query()
            ->where('activo', true)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get();

        $obras = Obra::query()
            ->whereIn('estatus_nuevo', [Obra::ESTATUS_PLANEACION, Obra::ESTATUS_EJECUCION])
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'clave_obra', 'estatus_nuevo']);
        $almacenes = Almacen::query()->where('activo', true)->orderBy('nombre')->get(['id', 'codigo', 'nombre', 'tipo']);
        $maquinas = Maquina::query()
            ->where('estado', '!=', Maquina::ESTADO_BAJA_DEFINITIVA)
            ->orderBy('codigo')
            ->orderBy('nombre')
            ->get(['id', 'codigo', 'nombre', 'tipo', 'estado', 'ubicacion']);
        $vehiculos = Vehiculo::query()
            ->orderBy('marca')
            ->orderBy('modelo')
            ->orderBy('placas')
            ->get(['id', 'marca', 'modelo', 'placas', 'tipo', 'estatus']);

        return view('reposicion-caja-chica.create', compact('categorias', 'subcategorias', 'obras', 'almacenes', 'maquinas', 'vehiculos'));
    }

    /**
     * Endpoint AJAX para procesar uno o múltiples archivos XML arrastrados.
     */
    public function parseXml(Request $request, CfdiXmlParserService $parser)
    {
        $request->validate([
            'xml_files' => ['required', 'array', 'min:1'],
            'xml_files.*' => ['file', 'mimes:xml,txt', 'max:5120'],
        ]);

        $resultados = [];
        $errores = [];

        foreach ($request->file('xml_files') as $idx => $file) {
            $nombreOriginal = $file->getClientOriginalName();
            try {
                $contenido = file_get_contents($file->getRealPath());
                $data = $parser->parse($contenido);
                $data['filename'] = $nombreOriginal;
                $data['file_index'] = $idx;
                $resultados[] = $data;
            } catch (\Throwable $e) {
                $errores[] = "{$nombreOriginal}: " . $e->getMessage();
            }
        }

        return response()->json([
            'ok' => count($resultados) > 0,
            'data' => $resultados,
            'errores' => $errores,
        ]);
    }

    /**
     * Guarda uno o un lote de gastos (captura tipo Excel).
     */
    public function store(Request $request)
    {
        // Determinar si viene como lote (array 'gastos') o como gasto único
        $items = $request->input('gastos');

        if (!is_array($items) || empty($items)) {
            // Soporte fallback para formulario simple
            $items = [
                [
                    'categoria_id' => $request->input('categoria_id'),
                    'subcategoria_id' => $request->input('subcategoria_id'),
                    'destino' => $request->input('destino', 'obra'),
                    'obra_id' => $request->input('obra_id'),
                    'almacen_id' => $request->input('almacen_id'),
                    'fecha_gasto' => $request->input('fecha_gasto'),
                    'proveedor_nombre' => $request->input('proveedor_nombre'),
                    'proveedor_rfc' => $request->input('proveedor_rfc'),
                    'concepto' => $request->input('concepto'),
                    'forma_pago' => $request->input('forma_pago'),
                    'importe_registrado' => $request->input('importe_registrado'),
                    'motivo_sin_factura' => $request->input('motivo_sin_factura'),
                    'observaciones' => $request->input('observaciones'),
                    'proveedor_id' => $request->input('proveedor_id'),
                ]
            ];
        }

        $targetDestino = $request->input('target_destino', $request->input('destino', 'obra'));
        if (!in_array($targetDestino, ['obra', 'almacen'], true)) {
            throw ValidationException::withMessages([
                'target_destino' => 'Selecciona un destino valido para el lote.',
            ]);
        }

        $targetObraId = $targetDestino === 'obra'
            ? $request->input('target_obra_id', $request->input('obra_id'))
            : null;
        $targetAlmacenId = $targetDestino === 'almacen'
            ? $request->input('target_almacen_id', $request->input('almacen_id'))
            : null;

        if ($targetDestino === 'obra') {
            if (empty($targetObraId)) {
                throw ValidationException::withMessages([
                    'target_obra_id' => 'Selecciona la obra destino para el lote.',
                ]);
            }

            $obraValida = Obra::query()
                ->where('id', $targetObraId)
                ->whereIn('estatus_nuevo', [Obra::ESTATUS_PLANEACION, Obra::ESTATUS_EJECUCION])
                ->exists();

            if (!$obraValida) {
                throw ValidationException::withMessages([
                    'target_obra_id' => 'La obra seleccionada no esta activa (solo se permiten obras en planeacion o en ejecucion).',
                ]);
            }
        }

        $targetAlmacen = null;
        $esAlmacenGiralda = false;

        if ($targetDestino === 'almacen') {
            if (empty($targetAlmacenId)) {
                throw ValidationException::withMessages([
                    'target_almacen_id' => 'Selecciona el almacen destino para el lote.',
                ]);
            }

            $targetAlmacen = Almacen::query()
                ->where('id', $targetAlmacenId)
                ->where('activo', true)
                ->first();

            if (!$targetAlmacen) {
                throw ValidationException::withMessages([
                    'target_almacen_id' => 'El almacen seleccionado no esta activo.',
                ]);
            }

            $esAlmacenGiralda = Str::contains(Str::lower((string) $targetAlmacen->codigo), 'gl')
                || Str::contains(Str::lower((string) $targetAlmacen->nombre), 'giralda');
        }

        $enviarDirecto = $request->input('action') === 'enviar';
        $estadoInicial = $enviarDirecto ? 'pendiente' : 'borrador';
        $userId = $request->user()->id;

        $categoriasMap = ReposicionCajaChicaCategoria::all()->keyBy('id');
        $gastosCreados = [];

        DB::beginTransaction();
        try {
            foreach ($items as $idx => $item) {
                $catId = $item['categoria_id'] ?? null;
                $categoria = $categoriasMap->get($catId);

                if (!$categoria) {
                    throw ValidationException::withMessages([
                        "gastos.{$idx}.categoria_id" => "Selecciona el tipo de comprobación para la fila #" . ($idx + 1),
                    ]);
                }

                if (empty($item['proveedor_nombre'])) {
                    throw ValidationException::withMessages([
                        "gastos.{$idx}.proveedor_nombre" => "El proveedor es obligatorio en la fila #" . ($idx + 1),
                    ]);
                }

                if (empty($item['concepto'])) {
                    throw ValidationException::withMessages([
                        "gastos.{$idx}.concepto" => "El concepto es obligatorio en la fila #" . ($idx + 1),
                    ]);
                }

                $proveedorId = $item['proveedor_id'] ?? null;
                if ($proveedorId !== null && $proveedorId !== '') {
                    $proveedor = Proveedor::query()
                        ->where('id', $proveedorId)
                        ->where('activo', true)
                        ->first();

                    if (!$proveedor) {
                        throw ValidationException::withMessages([
                            "gastos.{$idx}.proveedor_id" => "El proveedor seleccionado no esta activo o no existe en la fila #" . ($idx + 1),
                        ]);
                    }

                    $proveedorId = $proveedor->id;
                } else {
                    $proveedorId = null;
                }

                if (empty($item['importe_registrado']) || (float) $item['importe_registrado'] <= 0) {
                    throw ValidationException::withMessages([
                        "gastos.{$idx}.importe_registrado" => "El importe debe ser mayor a 0 en la fila #" . ($idx + 1),
                    ]);
                }

                $esParaActivo = filter_var($item['es_para_activo'] ?? ($item['es_para_maquina'] ?? false), FILTER_VALIDATE_BOOLEAN);
                $activoOperativo = trim((string) ($item['activo_operativo'] ?? ''));
                $maquinaId = null;
                $vehiculoId = null;

                if ($esParaActivo && !$esAlmacenGiralda) {
                    throw ValidationException::withMessages([
                        "gastos.{$idx}.es_para_activo" => "Solo puedes asignar activo operativo en gastos del almacen Giralda en la fila #" . ($idx + 1),
                    ]);
                }

                if ($esParaActivo) {
                    if ($activoOperativo === '' && !empty($item['maquina_id'])) {
                        $activoOperativo = 'maquina:' . $item['maquina_id'];
                    }

                    if ($activoOperativo === '') {
                        throw ValidationException::withMessages([
                            "gastos.{$idx}.activo_operativo" => "Selecciona el activo operativo para la fila #" . ($idx + 1),
                        ]);
                    }

                    [$activoTipo, $activoId] = array_pad(explode(':', $activoOperativo, 2), 2, null);
                    $activoId = (int) $activoId;

                    if (!in_array($activoTipo, ['maquina', 'vehiculo'], true) || $activoId <= 0) {
                        throw ValidationException::withMessages([
                            "gastos.{$idx}.activo_operativo" => "El activo operativo seleccionado no es valido en la fila #" . ($idx + 1),
                        ]);
                    }

                    if ($activoTipo === 'maquina') {
                        $maquinaValida = Maquina::query()
                            ->where('id', $activoId)
                            ->where('estado', '!=', Maquina::ESTADO_BAJA_DEFINITIVA)
                            ->exists();

                        if (!$maquinaValida) {
                            throw ValidationException::withMessages([
                                "gastos.{$idx}.activo_operativo" => "La maquina seleccionada no esta disponible en la fila #" . ($idx + 1),
                            ]);
                        }

                        $maquinaId = $activoId;
                    }

                    if ($activoTipo === 'vehiculo') {
                        $vehiculoValido = Vehiculo::query()
                            ->where('id', $activoId)
                            ->exists();

                        if (!$vehiculoValido) {
                            throw ValidationException::withMessages([
                                "gastos.{$idx}.activo_operativo" => "El vehiculo seleccionado no esta disponible en la fila #" . ($idx + 1),
                            ]);
                        }

                        $vehiculoId = $activoId;
                    }
                }

                $esParaMaquina = $maquinaId !== null;

                $destino = $targetDestino;
                $obraId = $targetDestino === 'obra' ? $targetObraId : null;
                $almacenId = $targetDestino === 'almacen' ? $targetAlmacenId : null;
                $formaPago = $item['forma_pago'] ?: $categoria->forma_pago_base;

                if (!in_array($formaPago, ['efectivo', 'tarjeta'], true)) {
                    throw ValidationException::withMessages([
                        "gastos.{$idx}.forma_pago" => "Solo se permiten pagos en efectivo o tarjeta en la fila #" . ($idx + 1),
                    ]);
                }

                // Motivo sin factura temporalmente no obligatorio; reactivar junto con el input de la vista cuando se requiera operativamente.

                $gasto = ReposicionCajaChicaGasto::create([
                    'categoria_id' => $categoria->id,
                    'subcategoria_id' => $item['subcategoria_id'] ?? null,
                    'destino' => $destino,
                    'obra_id' => $obraId,
                    'almacen_id' => $almacenId,
                    'es_para_maquina' => $esParaMaquina,
                    'maquina_id' => $maquinaId,
                    'vehiculo_id' => $vehiculoId,
                    'fecha_gasto' => $item['fecha_gasto'] ?: now()->toDateString(),
                    'proveedor_nombre' => $item['proveedor_nombre'],
                    'proveedor_id' => $proveedorId,
                    'proveedor_rfc' => $item['proveedor_rfc'] ?? null,
                    'concepto' => $item['concepto'],
                    'forma_pago' => $formaPago,
                    'importe_registrado' => (float) $item['importe_registrado'],
                    'estado_autorizacion' => $estadoInicial,
                    'motivo_sin_factura' => $item['motivo_sin_factura'] ?? null,
                    'observaciones' => $item['observaciones'] ?? null,
                    'solicitado_por' => $enviarDirecto ? $userId : null,
                    'solicitado_at' => $enviarDirecto ? now() : null,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);

                // Guardar archivo XML si fue subido para este renglón
                if ($request->hasFile("xml_files.{$idx}")) {
                    $xmlFile = $request->file("xml_files.{$idx}");
                    if ($xmlFile && $xmlFile->isValid()) {
                        $path = $xmlFile->store("reposicion-caja-chica/xml/{$gasto->id}", 'public');
                        ReposicionCajaChicaGastoArchivo::create([
                            'gasto_id' => $gasto->id,
                            'tipo' => 'xml',
                            'disk' => 'public',
                            'path' => $path,
                            'nombre_original' => $xmlFile->getClientOriginalName(),
                            'mime_type' => $xmlFile->getMimeType(),
                            'size_bytes' => $xmlFile->getSize(),
                            'hash_sha256' => hash_file('sha256', $xmlFile->getRealPath()),
                            'uploaded_by' => $userId,
                        ]);
                    }
                }

                // Guardar evidencias adjuntas para este renglón (pueden ser múltiples)
                $evidenciasFila = $request->file("evidencias.{$idx}", []);
                if (!is_array($evidenciasFila) && $evidenciasFila) {
                    $evidenciasFila = [$evidenciasFila];
                }

                foreach ($evidenciasFila as $archivo) {
                    if (!$archivo || !$archivo->isValid()) {
                        continue;
                    }

                    $path = $archivo->store("reposicion-caja-chica/evidencias/{$gasto->id}", 'public');
                    ReposicionCajaChicaGastoArchivo::create([
                        'gasto_id' => $gasto->id,
                        'tipo' => 'evidencia',
                        'disk' => 'public',
                        'path' => $path,
                        'nombre_original' => $archivo->getClientOriginalName(),
                        'mime_type' => $archivo->getMimeType(),
                        'size_bytes' => $archivo->getSize(),
                        'hash_sha256' => hash_file('sha256', $archivo->getRealPath()),
                        'uploaded_by' => $userId,
                    ]);
                }

                $gastosCreados[] = $gasto;
            }

            DB::commit();

            $totalGastos = count($gastosCreados);
            $msg = $enviarDirecto
                ? "Se enviaron {$totalGastos} gasto(s) a revisión de oficina."
                : "Se guardaron {$totalGastos} gasto(s) como borrador.";

            return redirect()
                ->route('reposicion-caja-chica.index')
                ->with('success', $msg);
        } catch (\Throwable $e) {
            DB::rollBack();
            if ($e instanceof ValidationException) {
                throw $e;
            }
            return back()->withInput()->with('error', 'Error al procesar los gastos: ' . $e->getMessage());
        }
    }

    public function imprimirReporte(Request $request)
    {
        [$fechaInicio, $fechaFin] = $this->resolverRangoSemana($request);
        $gastos = $this->gastosReporteQuery($request, $fechaInicio, $fechaFin)
            ->orderBy('categoria_id')
            ->orderBy('fecha_gasto')
            ->orderBy('id')
            ->get();

        $grupos = $this->agruparGastosPorCategoria($gastos);
        $stats = $this->stats($request, $fechaInicio, $fechaFin);
        $reporteContexto = $this->contextoImpresionReposicion($request);
        $ambitoFirma = $this->resolverAmbitoFirmaReposicion($request, $gastos);
        $firmasImpresas = $this->firmasImpresasReposicion($ambitoFirma);
        $elaboroNombre = $this->nombresUsuariosReporte($gastos->pluck('created_by'));
        $autorizoNombre = $this->nombresUsuariosReporte(
            $gastos
                ->whereIn('estado_autorizacion', ['autorizado', 'autorizado_parcial'])
                ->pluck('resuelto_por')
        );

        return view('reposicion-caja-chica.reporte-imprimir', compact('gastos', 'grupos', 'stats', 'fechaInicio', 'fechaFin', 'ambitoFirma', 'firmasImpresas', 'elaboroNombre', 'autorizoNombre', 'reporteContexto'));
    }

    public function exportarExcel(Request $request)
    {
        [$fechaInicio, $fechaFin] = $this->resolverRangoSemana($request);
        $gastos = $this->gastosReporteQuery($request, $fechaInicio, $fechaFin)
            ->orderBy('categoria_id')
            ->orderBy('fecha_gasto')
            ->orderBy('id')
            ->get();
        $grupos = $this->agruparGastosPorCategoria($gastos);
        $filename = 'reposicion-caja-chica-' . $fechaInicio->format('Ymd') . '-' . $fechaFin->format('Ymd') . '.xls';

        return response()->streamDownload(function () use ($grupos, $fechaInicio, $fechaFin) {
            echo "<html><head><meta charset=\"UTF-8\"></head><body>";
            echo "<h1>Reposicion de caja chica</h1>";
            echo "<p>Periodo: " . e($fechaInicio->format('d/m/Y')) . " al " . e($fechaFin->format('d/m/Y')) . "</p>";

            foreach ($grupos as $grupo) {
                echo "<h2>" . e($grupo['nombre']) . "</h2>";
                echo "<table border=\"1\">";
                echo "<thead><tr>";
                foreach (['Folio', 'Fecha gasto', 'Fecha captura', 'Proveedor', 'RFC', 'Concepto', 'Categoria', 'Forma pago', 'Destino', 'Activo', 'Registrado', 'Autorizado', 'Estado'] as $header) {
                    echo "<th>" . e($header) . "</th>";
                }
                echo "</tr></thead><tbody>";

                foreach ($grupo['gastos'] as $gasto) {
                    $destino = $gasto->destino === 'obra'
                        ? ($gasto->obra->nombre ?? 'Obra no definida')
                        : ($gasto->almacen->nombre ?? 'Almacen no definido');

                    echo "<tr>";
                    echo "<td>RCC-G-" . str_pad((string) $gasto->id, 5, '0', STR_PAD_LEFT) . "</td>";
                    echo "<td>" . e(optional($gasto->fecha_gasto)->format('d/m/Y')) . "</td>";
                    echo "<td>" . e(optional($gasto->created_at)->format('d/m/Y H:i')) . "</td>";
                    echo "<td>" . e($gasto->proveedor_nombre) . "</td>";
                    echo "<td>" . e($gasto->proveedor_rfc ?: '-') . "</td>";
                    echo "<td>" . e($gasto->concepto) . "</td>";
                    echo "<td>" . e($gasto->subcategoria->nombre ?? 'Sin categoria') . "</td>";
                    echo "<td>" . e(ucfirst((string) $gasto->forma_pago)) . "</td>";
                    echo "<td>" . e($destino) . "</td>";
                    echo "<td>" . e($gasto->activo_operativo_label) . "</td>";
                    echo "<td>" . number_format((float) $gasto->importe_registrado, 2) . "</td>";
                    echo "<td>" . number_format((float) ($gasto->importe_autorizado ?? 0), 2) . "</td>";
                    echo "<td>" . e(str_replace('_', ' ', $gasto->estado_autorizacion)) . "</td>";
                    echo "</tr>";
                }

                echo "<tr><td colspan=\"10\"><strong>Total " . e($grupo['nombre']) . "</strong></td>";
                echo "<td><strong>" . number_format((float) $grupo['total_registrado'], 2) . "</strong></td>";
                echo "<td><strong>" . number_format((float) $grupo['total_autorizado'], 2) . "</strong></td><td></td></tr>";
                echo "</tbody></table><br>";
            }

            echo "</body></html>";
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
        ]);
    }
    public function show(ReposicionCajaChicaGasto $gasto)
    {
        $gasto->load(['categoria', 'subcategoria', 'obra', 'almacen', 'maquina', 'vehiculo', 'archivos', 'solicitadoPor', 'resueltoPor']);

        return view('reposicion-caja-chica.show', compact('gasto'));
    }

    public function destroy(Request $request, ReposicionCajaChicaGasto $gasto)
    {
        $this->authorizeAny(['caja_chica.delete.access'], 'No tienes permiso para eliminar gastos de caja chica.');

        if (in_array($gasto->estado_autorizacion, ['autorizado', 'autorizado_parcial'], true)) {
            return back()->with('error', 'No se puede eliminar un gasto autorizado.');
        }

        if ($gasto->relacion_id) {
            return back()->with('error', 'No se puede eliminar un gasto que ya pertenece a una relacion.');
        }

        DB::transaction(function () use ($gasto) {
            $gasto->load('archivos');

            foreach ($gasto->archivos as $archivo) {
                if ($archivo->path) {
                    Storage::disk($archivo->disk ?: 'public')->delete($archivo->path);
                }

                $archivo->delete();
            }

            $gasto->delete();
        });

        return redirect()
            ->route('reposicion-caja-chica.index', $request->query())
            ->with('success', 'Gasto eliminado correctamente.');
    }

    public function revision(Request $request)
    {
        $gastos = ReposicionCajaChicaGasto::query()
            ->with(['categoria', 'subcategoria', 'obra', 'almacen', 'maquina', 'vehiculo', 'solicitadoPor'])
            ->whereIn('estado_autorizacion', ['pendiente', 'autorizado', 'autorizado_parcial', 'rechazado'])
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('reposicion-caja-chica.revision', compact('gastos'));
    }

    public function autorizar(Request $request, ReposicionCajaChicaGasto $gasto)
    {
        $this->authorizeAny(['caja_chica.authorize.access'], 'No tienes permiso para autorizar gastos de caja chica.');
        $this->validarGastoPendiente($gasto);

        $gasto->update([
            'estado_autorizacion' => 'autorizado',
            'importe_autorizado' => $gasto->importe_registrado,
            'resuelto_por' => $request->user()->id,
            'resuelto_at' => now(),
            'motivo_rechazo' => null,
            'observaciones_autorizacion' => $request->input('observaciones_autorizacion'),
            'updated_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Gasto autorizado correctamente.');
    }

    public function autorizarParcial(Request $request, ReposicionCajaChicaGasto $gasto)
    {
        $this->authorizeAny(['caja_chica.authorize.access'], 'No tienes permiso para autorizar gastos de caja chica.');
        $this->validarGastoPendiente($gasto);

        $validated = $request->validate([
            'importe_autorizado' => ['required', 'numeric', 'min:0.01', 'lt:' . $gasto->importe_registrado],
            'observaciones_autorizacion' => ['nullable', 'string', 'max:2000'],
        ]);

        $gasto->update([
            'estado_autorizacion' => 'autorizado_parcial',
            'importe_autorizado' => (float) $validated['importe_autorizado'],
            'resuelto_por' => $request->user()->id,
            'resuelto_at' => now(),
            'motivo_rechazo' => null,
            'observaciones_autorizacion' => $validated['observaciones_autorizacion'] ?? null,
            'updated_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Gasto autorizado parcialmente.');
    }

    public function rechazar(Request $request, ReposicionCajaChicaGasto $gasto)
    {
        $this->authorizeAny(['caja_chica.reject.access', 'caja_chica.authorize.access'], 'No tienes permiso para rechazar gastos de caja chica.');
        $this->validarGastoPendiente($gasto);

        $validated = $request->validate([
            'motivo_rechazo' => ['required', 'string', 'max:2000'],
            'observaciones_autorizacion' => ['nullable', 'string', 'max:2000'],
        ]);

        $gasto->update([
            'estado_autorizacion' => 'rechazado',
            'importe_autorizado' => 0,
            'resuelto_por' => $request->user()->id,
            'resuelto_at' => now(),
            'motivo_rechazo' => $validated['motivo_rechazo'],
            'observaciones_autorizacion' => $validated['observaciones_autorizacion'] ?? null,
            'updated_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Gasto rechazado correctamente.');
    }

    public function relaciones()
    {
        $relaciones = ReposicionCajaChicaRelacion::query()
            ->with(['responsable', 'almacen'])
            ->latest('id')
            ->paginate(20);

        return view('reposicion-caja-chica.relaciones', compact('relaciones'));
    }

    public function imprimirRelacion(ReposicionCajaChicaRelacion $relacion)
    {
        $relacion->load([
            'responsable',
            'almacen',
            'gastos.categoria',
            'gastos.subcategoria',
            'gastos.obra',
            'gastos.almacen',
            'gastos.maquina',
            'gastos.solicitadoPor',
            'gastos.resueltoPor',
        ]);

        $ambitoFirma = $this->resolverAmbitoFirmaReposicion(null, $relacion->gastos, $relacion->almacen?->nombre);
        $firmasImpresas = $this->firmasImpresasReposicion($ambitoFirma);

        return view('reposicion-caja-chica.relaciones-imprimir', compact('relacion', 'ambitoFirma', 'firmasImpresas'));
    }

    private function puedeElegirAmbitoFirmaReposicion(?Request $request = null): bool
    {
        return (bool) $request?->user()?->hasAnyRole(['admin-rivera', 'super-admin']);
    }
    private function resolverAmbitoFirmaReposicion(?Request $request = null, $gastos = null, ?string $almacenNombre = null): string
    {
        $ambitosValidos = [
            DocumentoFirmante::AMBITO_REPOSICION_GASTOS_ALMACEN,
            DocumentoFirmante::AMBITO_GIRALDA,
        ];

        $ambitoSolicitado = $request?->input('ambito');
        if ($this->puedeElegirAmbitoFirmaReposicion($request) && in_array($ambitoSolicitado, $ambitosValidos, true)) {
            return $ambitoSolicitado;
        }

        if ($almacenNombre && Str::contains(Str::lower($almacenNombre), 'giralda')) {
            return DocumentoFirmante::AMBITO_GIRALDA;
        }

        $gastos = collect($gastos ?? []);
        $gastosGiralda = $gastos->filter(function ($gasto) {
            return $gasto->almacen && Str::contains(Str::lower((string) $gasto->almacen->nombre), 'giralda');
        });

        if ($gastos->isNotEmpty() && $gastosGiralda->count() === $gastos->count()) {
            return DocumentoFirmante::AMBITO_GIRALDA;
        }

        return DocumentoFirmante::AMBITO_REPOSICION_GASTOS_ALMACEN;
    }

    private function resolverAmbitoFirmaReposicionDisponible(Request $request, $gastos = null): string
    {
        $ambito = $this->resolverAmbitoFirmaReposicion($request, $gastos);

        if ($request->filled('ambito') || $this->existenFirmasImpresasReposicion($ambito)) {
            return $ambito;
        }

        foreach ([DocumentoFirmante::AMBITO_GIRALDA, DocumentoFirmante::AMBITO_REPOSICION_GASTOS_ALMACEN] as $fallbackAmbito) {
            if ($fallbackAmbito !== $ambito && $this->existenFirmasImpresasReposicion($fallbackAmbito)) {
                return $fallbackAmbito;
            }
        }

        return $ambito;
    }

    private function existenFirmasImpresasReposicion(string $ambito): bool
    {
        return DocumentoFirmante::query()
            ->where('documento', DocumentoFirmante::DOCUMENTO_REPOSICION_CAJA_CHICA)
            ->where('ambito', $ambito)
            ->where('activo', true)
            ->exists();
    }

    private function firmasImpresasReposicion(string $ambito)
    {
        return DocumentoFirmante::query()
            ->with('user:id,name')
            ->where('documento', DocumentoFirmante::DOCUMENTO_REPOSICION_CAJA_CHICA)
            ->where('ambito', $ambito)
            ->where('activo', true)
            ->whereIn('campo', [
                DocumentoFirmante::CAMPO_ELABORO,
                DocumentoFirmante::CAMPO_VOBO,
                DocumentoFirmante::CAMPO_AUTORIZO,
            ])
            ->get()
            ->keyBy('campo');
    }
    private function nombresUsuariosReporte($userIds): string
    {
        $ids = collect($userIds)
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return '';
        }

        return User::query()
            ->whereIn('id', $ids)
            ->orderBy('name')
            ->pluck('name')
            ->implode(', ');
    }
    private function validarGastoPendiente(ReposicionCajaChicaGasto $gasto): void
    {
        if ($gasto->estado_autorizacion !== 'pendiente') {
            throw ValidationException::withMessages([
                'gasto' => 'Solo se pueden resolver gastos pendientes de autorizacion.',
            ]);
        }
    }

    private function authorizeAny(array $permissions, string $message): void
    {
        $user = auth()->user();

        if (!$user || !$user->canAny($permissions)) {
            abort(403, $message);
        }
    }
    private function resolverRangoSemana(Request $request): array
    {
        try {
            $fechaInicio = $request->filled('fecha_inicio')
                ? Carbon::parse($request->fecha_inicio)->startOfDay()
                : now()->startOfWeek(Carbon::MONDAY)->startOfDay();
        } catch (\Throwable $e) {
            $fechaInicio = now()->startOfWeek(Carbon::MONDAY)->startOfDay();
        }

        try {
            $fechaFin = $request->filled('fecha_fin')
                ? Carbon::parse($request->fecha_fin)->endOfDay()
                : $fechaInicio->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();
        } catch (\Throwable $e) {
            $fechaFin = $fechaInicio->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();
        }

        if ($fechaFin->lt($fechaInicio)) {
            $fechaFin = $fechaInicio->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();
        }

        return [$fechaInicio, $fechaFin];
    }

    private function contextoImpresionReposicion(Request $request): string
    {
        if ($request->input('destino') === 'obra' && $request->filled('obra_id')) {
            $obra = Obra::find($request->integer('obra_id'));
            $nombre = trim(($obra?->clave_obra ? $obra->clave_obra . ' - ' : '') . ($obra?->nombre ?? 'Obra no definida'));

            return 'Obra: ' . $nombre;
        }

        if ($request->input('destino') === 'almacen' && $request->filled('almacen_id')) {
            $almacen = Almacen::find($request->integer('almacen_id'));

            return 'Almacen: ' . ($almacen?->nombre ?? 'Almacen no definido');
        }

        return 'Reporte general';
    }
    private function destinosImpresionReposicion(Request $request, Carbon $fechaInicio, Carbon $fechaFin)
    {
        $queryRequest = $request->duplicate(
            array_merge($request->query(), [
                'destino' => null,
                'obra_id' => null,
                'almacen_id' => null,
            ])
        );

        $gastos = $this->gastosReporteQuery($queryRequest, $fechaInicio, $fechaFin)
            ->where(function ($query) {
                $query
                    ->whereNotNull('obra_id')
                    ->orWhereNotNull('almacen_id');
            })
            ->get();

        $destinos = collect();

        $gastos
            ->where('destino', 'almacen')
            ->whereNotNull('almacen_id')
            ->groupBy('almacen_id')
            ->each(function ($items, $almacenId) use ($destinos) {
                $almacen = $items->first()->almacen;

                $destinos->push([
                    'tipo' => 'almacen',
                    'id' => (int) $almacenId,
                    'label' => 'Almacen: ' . ($almacen?->nombre ?? 'Sin almacen'),
                ]);
            });

        $gastos
            ->where('destino', 'obra')
            ->whereNotNull('obra_id')
            ->groupBy('obra_id')
            ->each(function ($items, $obraId) use ($destinos) {
                $obra = $items->first()->obra;
                $nombre = trim(($obra?->clave_obra ? $obra->clave_obra . ' - ' : '') . ($obra?->nombre ?? 'Sin obra'));

                $destinos->push([
                    'tipo' => 'obra',
                    'id' => (int) $obraId,
                    'label' => 'Obra: ' . $nombre,
                ]);
            });

        return $destinos
            ->sortBy([['tipo', 'asc'], ['label', 'asc']])
            ->values();
    }
    private function gastosReporteQuery(Request $request, Carbon $fechaInicio, Carbon $fechaFin)
    {
        return ReposicionCajaChicaGasto::query()
            ->with(['categoria', 'subcategoria', 'obra', 'almacen', 'maquina', 'vehiculo', 'solicitadoPor'])
            ->whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->when($request->filled('q'), function ($query) use ($request) {
                $q = trim((string) $request->q);

                $query->where(function ($searchQuery) use ($q) {
                    $searchQuery
                        ->where('proveedor_nombre', 'like', "%{$q}%")
                        ->orWhere('proveedor_rfc', 'like', "%{$q}%")
                        ->orWhere('concepto', 'like', "%{$q}%")
                        ->orWhere('uuid', 'like', "%{$q}%")
                        ->orWhere('id', $q)
                        ->orWhereHas('obra', fn ($obraQuery) => $obraQuery->where('nombre', 'like', "%{$q}%"))
                        ->orWhereHas('almacen', fn ($almacenQuery) => $almacenQuery->where('nombre', 'like', "%{$q}%"));
                });
            })
            ->when($request->filled('estado'), fn ($query) => $query->where('estado_autorizacion', $request->estado))
            ->when($request->filled('categoria_id'), fn ($query) => $query->where('categoria_id', $request->categoria_id))
            ->when($request->filled('destino'), fn ($query) => $query->where('destino', $request->destino))
            ->when($request->filled('obra_id'), fn ($query) => $query->where('obra_id', $request->integer('obra_id')))
            ->when($request->filled('almacen_id'), fn ($query) => $query->where('almacen_id', $request->integer('almacen_id')));
    }

    private function agruparGastosPorCategoria($gastos)
    {
        return $gastos
            ->groupBy(fn ($gasto) => $gasto->categoria->codigo ?? 'sin_categoria')
            ->map(fn ($items) => [
                'nombre' => $items->first()->categoria->nombre ?? 'Sin categoria',
                'gastos' => $items->values(),
                'total_registrado' => $items->sum('importe_registrado'),
                'total_autorizado' => $items->sum(fn ($gasto) => (float) ($gasto->importe_autorizado ?? 0)),
            ])
            ->sortBy('nombre')
            ->values();
    }

    private function categoriasActivas()
    {
        return ReposicionCajaChicaCategoria::query()
            ->where('activo', true)
            ->with(['subcategorias' => fn ($query) => $query->where('activo', true)->orderBy('orden')->orderBy('nombre')])
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get();
    }

    private function stats(Request $request, Carbon $fechaInicio, Carbon $fechaFin): array
    {
        $base = $this->gastosReporteQuery($request, $fechaInicio, $fechaFin);

        return [
            'borrador' => (clone $base)->where('estado_autorizacion', 'borrador')->count(),
            'pendiente' => (clone $base)->where('estado_autorizacion', 'pendiente')->count(),
            'autorizado' => (clone $base)->whereIn('estado_autorizacion', ['autorizado', 'autorizado_parcial'])->sum('importe_autorizado'),
            'registrado' => (clone $base)->sum('importe_registrado'),
        ];
    }
}









