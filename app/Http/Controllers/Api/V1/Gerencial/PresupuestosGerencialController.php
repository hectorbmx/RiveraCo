<?php

namespace App\Http\Controllers\Api\V1\Gerencial;

use App\Http\Controllers\Controller;
use App\Models\Presupuesto;
use App\Models\PresupuestoVersion;
use App\Services\Presupuestos\ConsultaPresupuesto;
use App\Services\Presupuestos\PdfPresupuesto;
use App\Support\PresentacionPresupuesto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;

class PresupuestosGerencialController extends Controller
{
    public function __construct(
        private ConsultaPresupuesto $consulta,
        private PdfPresupuesto $pdf,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $filtros = $request->validate([
            'buscar' => ['nullable', 'string', 'max:255'],
            'tipo' => ['nullable', 'string', Rule::in(array_keys(Presupuesto::TIPOS))],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'between:1,50'],
        ]);
        $pagina = $this->consulta->listado(trim($filtros['buscar'] ?? ''), $filtros['tipo'] ?? null)
            ->paginate($filtros['per_page'] ?? 20);
        $this->consulta->cargarImportes($pagina->getCollection());

        return $this->pagina($pagina, function (Presupuesto $presupuesto): array {
            $version = $this->consulta->versionPreferida($presupuesto);
            $importes = $this->consulta->importes($presupuesto, $version);

            return $this->cabecera($presupuesto, $version) + [
                'costo_directo' => $this->importe($importes['directo']),
                'total' => $this->importe($importes['total']),
                'versiones_count' => $presupuesto->versiones_count,
            ];
        });
    }

    public function show(Presupuesto $presupuesto): JsonResponse
    {
        return $this->detalle($presupuesto, $this->consulta->versionPreferida($presupuesto));
    }

    public function versiones(Request $request, Presupuesto $presupuesto): JsonResponse
    {
        $filtros = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'between:1,50'],
        ]);
        $pagina = $presupuesto->versiones()->with('ultimoEvento')->orderByDesc('revision')
            ->paginate($filtros['per_page'] ?? 20);

        return $this->pagina($pagina, function (PresupuestoVersion $version) use ($presupuesto): array {
            $evento = $version->ultimoEvento;

            return $this->referencia($presupuesto, $version)['version'] + [
                'motivo' => $version->motivo,
                'fecha' => $version->created_at->toISOString(),
                'total' => $this->importe($this->consulta->importes($presupuesto, $version)['total']),
                'ultimo_evento' => $evento ? ['tipo' => $evento->tipo, 'etiqueta' => $evento->etiqueta()] : null,
            ];
        });
    }

    public function showVersion(Presupuesto $presupuesto, int $version): JsonResponse
    {
        return $this->detalle($presupuesto, $presupuesto->versiones()->findOrFail($version));
    }

    public function pdf(Request $request, Presupuesto $presupuesto)
    {
        $borrador = $request->boolean('borrador');
        abort_if($borrador && $presupuesto->estaAprobado(), 409, 'El presupuesto ya tiene una versión aprobada.');
        $version = $borrador ? null : $this->consulta->versionPreferida($presupuesto);

        return $this->exportar($presupuesto, $version);
    }

    public function pdfVersion(Presupuesto $presupuesto, int $version)
    {
        return $this->exportar($presupuesto, $presupuesto->versiones()->findOrFail($version));
    }

    private function detalle(Presupuesto $presupuesto, ?PresupuestoVersion $version): JsonResponse
    {
        $presentacion = $this->presentacion($presupuesto, $version);
        $pdfDisponible = $this->puedeExportar($presupuesto, $version, $presentacion);
        $urlPdf = $version
            ? route('api.gerencial.presupuestos.versiones.pdf', [$presupuesto, $version->id], false)
            : route('api.gerencial.presupuestos.pdf', [$presupuesto, 'borrador' => 1], false);
        $partidas = null;
        if (isset($presentacion['partidas'])) {
            $partidas = collect($presentacion['partidas'])->map(fn (array $partida) => [
                'clave' => $partida['clave'],
                'nombre' => $partida['nombre'],
                'subtotal' => $this->importe($partida['subtotal']),
                'conceptos' => array_map(
                    fn (array $fila) => $this->concepto($fila + ['partida' => $partida['nombre']]),
                    $partida['subpartidas'],
                ),
            ])->all();
            $conceptos = collect($partidas)->pluck('conceptos')->collapse()->values();
        } else {
            $filas = collect($presentacion['filas']);
            if ($version) {
                $filas = PresentacionPresupuesto::numerar($filas);
            }
            $conceptos = $filas->map(fn (array $fila) => $this->concepto($fila))->values();
        }

        return response()->json(['ok' => true, 'data' => $this->cabecera($presupuesto, $version) + [
            'conceptos' => $conceptos,
            'partidas' => $partidas,
            'descripcion_perforacion' => $presentacion['descripcion_perforacion'] ?? null,
            'subtotal' => $this->importe($presentacion['subtotal'] ?? null),
            'tasa_iva' => $this->importe($presentacion['tasa_iva'] ?? null),
            'iva' => $this->importe($presentacion['iva'] ?? null),
            'total' => $this->importe($presentacion['total']),
            'pdf_disponible' => $pdfDisponible,
            'pdf_url' => $pdfDisponible ? $urlPdf : null,
        ]]);
    }

    private function concepto(array $fila): array
    {
        return [
            'numero' => $fila['numero'] ?? null,
            'numero_encabezado' => $fila['numero_encabezado'] ?? null,
            'partida' => $fila['partida'],
            'concepto' => $fila['concepto'],
            'unidad' => $fila['unidad'],
            'cantidad' => $this->importe($fila['cantidad']),
            'precio_unitario' => $this->importe($fila['precio_unitario']),
            'importe' => $this->importe($fila['importe']),
        ];
    }

    private function presentacion(Presupuesto $presupuesto, ?PresupuestoVersion $version): array
    {
        if ($version) {
            return $this->consulta->presentacionVersion($version);
        }

        if ($presupuesto->esImportado()) {
            return [
                'filas' => $presupuesto->resumenes()->orderBy('id')
                    ->get(['partida', 'concepto', 'unidad', 'cantidad', 'precio_unitario', 'importe'])->toArray(),
                'total' => $presupuesto->total_presupuesto,
            ];
        }

        return $this->consulta->borradorComercial($presupuesto)['presentacion'];
    }

    private function puedeExportar(Presupuesto $presupuesto, ?PresupuestoVersion $version, array $presentacion): bool
    {
        return ($version !== null || ! $presupuesto->esImportado()) && PresentacionPresupuesto::tieneConceptos($presentacion);
    }

    private function exportar(Presupuesto $presupuesto, ?PresupuestoVersion $version)
    {
        $presentacion = $this->presentacion($presupuesto, $version);
        abort_unless($this->puedeExportar($presupuesto, $version, $presentacion), 422, 'No hay una propuesta comercial exportable.');

        return $version
            ? $this->pdf->deVersion($presupuesto, $version, $presentacion)
            : $this->pdf->delPresupuesto($presupuesto, $presentacion);
    }

    private function cabecera(Presupuesto $presupuesto, ?PresupuestoVersion $version): array
    {
        $datos = $version?->datos;

        return [
            'id' => $presupuesto->id,
            'folio' => $datos['presupuesto']['codigo_proyecto'] ?? $presupuesto->codigo_proyecto,
            'cliente' => ($datos['cliente']['nombre_comercial'] ?? $datos['presupuesto']['nombre_cliente'] ?? null)
                ?: $presupuesto->nombreClienteVisible(),
            'obra' => $version ? ($datos['presupuesto']['nombre_obra'] ?? null) : $presupuesto->nombre_obra,
            'tipo' => $presupuesto->tipo,
            'estatus' => $presupuesto->estatus,
            'origen' => $presupuesto->origen,
        ] + $this->referencia($presupuesto, $version);
    }

    private function referencia(Presupuesto $presupuesto, ?PresupuestoVersion $version): array
    {
        $version?->setRelation('presupuesto', $presupuesto);

        return [
            'fuente' => $version ? 'version' : ($presupuesto->esImportado() ? 'excel' : 'borrador'),
            'version' => $version ? [
                'id' => $version->id,
                'folio' => $version->folio(),
                'revision' => $version->revision,
                'aprobada' => (int) $presupuesto->version_aprobada_id === $version->id,
            ] : null,
        ];
    }

    private function importe($valor): ?float
    {
        return $valor === null ? null : (float) $valor;
    }

    private function pagina(LengthAwarePaginator $pagina, callable $transformar): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'data' => $pagina->getCollection()->map($transformar),
            'meta' => [
                'current_page' => $pagina->currentPage(),
                'last_page' => $pagina->lastPage(),
                'per_page' => $pagina->perPage(),
                'total' => $pagina->total(),
            ],
        ]);
    }
}
