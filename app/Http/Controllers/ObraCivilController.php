<?php

namespace App\Http\Controllers;

use App\Models\CivilCatalogImport;
use App\Models\CivilConcept;
use App\Models\CivilEstimation;
use App\Models\Obra;
use App\Models\ObraCivilInsumo;
use App\Models\ObraCivilInsumoImport;
use App\Services\CivilCatalogExcelParser;
use App\Services\CivilConceptBalanceService;
use App\Services\ObraCivilInsumoExcelParser;
use App\Services\ObraCivilInsumoBalanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class ObraCivilController extends Controller
{
    public function index()
    {
        $catalogTablesReady = Schema::hasTable('civil_catalog_imports')
            && Schema::hasTable('civil_buildings')
            && Schema::hasTable('civil_partidas')
            && Schema::hasTable('civil_concepts');

        $obrasQuery = Obra::query()
            ->whereIn('tipo_obra', ['OBRA_CIVIL', 'CIVIL'])
            ->with('cliente')
            ->orderBy('nombre');

        if ($catalogTablesReady) {
            $obrasQuery->withCount('civilCatalogImports');
        }

        $obras = $obrasQuery->get();

        if (!$catalogTablesReady) {
            $obras->each(fn ($obra) => $obra->civil_catalog_imports_count = 0);
        }

        $catalogImports = $catalogTablesReady
            ? CivilCatalogImport::query()
                ->with(['obra', 'importedBy'])
                ->latest()
                ->limit(8)
                ->get()
            : collect();

        $stats = [
            'obras' => $obras->count(),
            'catalogos' => $catalogTablesReady ? CivilCatalogImport::whereHas('obra')->count() : 0,
            'conceptos' => $catalogTablesReady ? CivilConcept::whereHas('partida.building.catalogImport.obra')->count() : 0,
            'importe' => $catalogTablesReady ? (float) CivilConcept::whereHas('partida.building.catalogImport.obra')->sum('budget_amount') : 0.0,
        ];

        return view('obra_civil.index', compact('obras', 'catalogImports', 'stats', 'catalogTablesReady'));
    }
    public function uploadCatalog(Request $request, Obra $obra, CivilCatalogExcelParser $parser)
    {
        $this->abortUnlessCivil($obra);

        $data = $request->validate([
            'catalogo' => ['required', 'file', 'mimes:xlsx,xlsm,xls,csv', 'max:20480'],
        ]);

        $file = $data['catalogo'];
        $originalName = $file->getClientOriginalName();
        $safeName = now()->format('Ymd_His') . '_' . Str::slug(pathinfo($originalName, PATHINFO_FILENAME));
        $extension = strtolower($file->getClientOriginalExtension());
        $filename = $safeName . '.' . $extension;
        $path = $file->storeAs("obra_civil/{$obra->id}", $filename);

        $import = CivilCatalogImport::create([
            'obra_id' => $obra->id,
            'filename' => $originalName,
            'original_path' => $path,
            'sheet_name' => 'CATALOGO',
            'status' => 'draft',
            'imported_by' => auth()->id(),
            'metadata' => [
                'stored_filename' => $filename,
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
                'parser_status' => 'pending',
                'warnings' => [],
            ],
        ]);

        try {
            $parser->parse($import, Storage::path($path));
        } catch (Throwable $exception) {
            $import->update([
                'metadata' => array_merge($import->metadata ?? [], [
                    'parser_status' => 'failed',
                    'warnings' => [
                        'El archivo se cargo, pero no se pudo leer automaticamente: ' . $exception->getMessage(),
                    ],
                ]),
            ]);
        }

        return redirect()->route('obra_civil.catalog.preview', [$obra, $import]);
    }

    public function uploadInsumos(Request $request, Obra $obra, ObraCivilInsumoExcelParser $parser)
    {
        $this->abortUnlessCivil($obra);

        $data = $request->validate([
            'insumos' => ['required', 'file', 'mimes:xlsx,xlsm', 'max:20480'],
        ]);

        $file = $data['insumos'];
        $originalName = $file->getClientOriginalName();
        $safeName = now()->format('Ymd_His') . '_' . Str::slug(pathinfo($originalName, PATHINFO_FILENAME));
        $extension = strtolower($file->getClientOriginalExtension());
        $filename = $safeName . '.' . $extension;
        $path = $file->storeAs("obra_civil/{$obra->id}/insumos", $filename);

        $import = ObraCivilInsumoImport::create([
            'obra_id' => $obra->id,
            'filename' => $originalName,
            'original_path' => $path,
            'status' => 'processing',
            'imported_by' => auth()->id(),
            'metadata' => [
                'stored_filename' => $filename,
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
                'parser_status' => 'pending',
                'warnings' => [],
            ],
        ]);

        try {
            $summary = $parser->parse($import, Storage::path($path));
        } catch (Throwable $exception) {
            $import->update([
                'status' => 'failed',
                'metadata' => array_merge($import->metadata ?? [], [
                    'parser_status' => 'failed',
                    'warnings' => [
                        'El archivo se cargo, pero no se pudo leer automaticamente: ' . $exception->getMessage(),
                    ],
                ]),
            ]);

            return back()->with('error', 'No se pudo importar la explosion de insumos: ' . $exception->getMessage());
        }

        return redirect()
            ->route('obra_civil.details', $obra)
            ->with('success', 'Explosion de insumos importada: ' . number_format($summary['insumos']) . ' insumos detectados.');
    }
    public function preview(Obra $obra, CivilCatalogImport $import)
    {
        $this->abortUnlessCivil($obra);
        $this->abortUnlessImportBelongsToObra($obra, $import);

        $import->load('buildings.partidas.concepts', 'importedBy');

        return view('obra_civil.preview', compact('obra', 'import'));
    }

    public function confirmCatalog(Request $request, Obra $obra, CivilCatalogImport $import)
    {
        $this->abortUnlessCivil($obra);
        $this->abortUnlessImportBelongsToObra($obra, $import);

        if ((int) $import->total_concepts === 0) {
            return back()->with('error', 'No hay conceptos detectados para guardar. Revisa las advertencias del preview o carga otro archivo.');
        }

        $import->update([
            'status' => 'imported',
            'validated_by' => auth()->id(),
            'validated_at' => now(),
        ]);

        return redirect()->route('obra_civil.details', $obra)
            ->with('success', 'Catalogo civil guardado correctamente.');
    }

    public function details(Obra $obra)
    {
        $this->abortUnlessCivil($obra);

        $obra->load('cliente');

        $imports = CivilCatalogImport::query()
            ->where('obra_id', $obra->id)
            ->with('importedBy')
            ->latest()
            ->get();

        $activeImport = CivilCatalogImport::query()
            ->where('obra_id', $obra->id)
            ->whereIn('status', ['imported', 'validated'])
            ->with('buildings.partidas.concepts')
            ->latest()
            ->first();

        $drafts = CivilCatalogImport::query()
            ->where('obra_id', $obra->id)
            ->where('status', 'draft')
            ->latest()
            ->get();

        $activeInsumoImport = ObraCivilInsumoImport::query()
            ->where('obra_id', $obra->id)
            ->where('status', 'imported')
            ->latest()
            ->first();

        $activeInsumos = ObraCivilInsumo::query()
            ->where('obra_id', $obra->id)
            ->where('is_active', true);

        $insumoStats = [
            'total' => (clone $activeInsumos)->count(),
            'materiales' => (clone $activeInsumos)->where('tipo', 'material')->count(),
            'mano_obra' => (clone $activeInsumos)->where('tipo', 'mano_obra')->count(),
            'equipo_herramienta' => (clone $activeInsumos)->where('tipo', 'equipo_herramienta')->count(),
            'importe_materiales' => (float) (clone $activeInsumos)->where('tipo', 'material')->sum('importe_importado'),
            'importe_total' => (float) (clone $activeInsumos)->sum('importe_importado'),
        ];
        $estimations = CivilEstimation::query()
            ->where('obra_id', $obra->id)
            ->with('createdBy')
            ->latest()
            ->limit(20)
            ->get();
        $balances = collect();

        if ($activeImport) {
            $conceptIds = $activeImport->buildings
                ->flatMap(fn ($building) => $building->partidas)
                ->flatMap(fn ($partida) => $partida->concepts)
                ->pluck('id');

            $balances = app(CivilConceptBalanceService::class)->summaries($conceptIds);
        }

        return view('obra_civil.details', compact('obra', 'imports', 'activeImport', 'drafts', 'balances', 'activeInsumoImport', 'insumoStats'));
    }

    public function insumos(Request $request, Obra $obra)
    {
        $this->abortUnlessCivil($obra);

        $obra->load('cliente');

        $insumoImports = ObraCivilInsumoImport::query()
            ->where('obra_id', $obra->id)
            ->with('importedBy')
            ->latest()
            ->get();

        $activeInsumoImport = ObraCivilInsumoImport::query()
            ->where('obra_id', $obra->id)
            ->where('status', 'imported')
            ->latest()
            ->first();

        $search = trim((string) $request->query('q', ''));
        $activeInsumosQuery = ObraCivilInsumo::query()
            ->where('obra_id', $obra->id)
            ->where('is_active', true);

        $allActiveInsumos = (clone $activeInsumosQuery)->get();
        $allInsumoBalances = app(ObraCivilInsumoBalanceService::class)->summaries($allActiveInsumos->pluck('id'));

        $insumosQuery = (clone $activeInsumosQuery)
            ->when($search !== '', function ($query) use ($search) {
                $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $search) . '%';

                $query->where(function ($query) use ($like) {
                    $query->where('codigo', 'like', $like)
                        ->orWhere('concepto', 'like', $like)
                        ->orWhere('unidad', 'like', $like)
                        ->orWhere('tipo', 'like', $like);
                });
            })
            ->orderBy('sort_order');

        $insumos = $insumosQuery->get();
        $insumoBalances = $allInsumoBalances->only($insumos->pluck('id')->all());

        $insumoStats = [
            'total' => $allActiveInsumos->count(),
            'materiales' => $allActiveInsumos->where('tipo', 'material')->count(),
            'mano_obra' => $allActiveInsumos->where('tipo', 'mano_obra')->count(),
            'equipo_herramienta' => $allActiveInsumos->where('tipo', 'equipo_herramienta')->count(),
            'importe_materiales' => $allActiveInsumos->where('tipo', 'material')->sum(fn ($item) => (float) $item->importe_importado),
            'importe_total' => $allActiveInsumos->sum(fn ($item) => (float) $item->importe_importado),
            'usado_total' => $allInsumoBalances->sum(fn ($balance) => (float) ($balance['used_amount'] ?? 0)),
        ];

        return view('obra_civil.insumos.index', compact('obra', 'insumoImports', 'activeInsumoImport', 'insumos', 'insumoStats', 'insumoBalances', 'search'));
    }

    public function destroyInsumoImport(Obra $obra, ObraCivilInsumoImport $import)
    {
        $this->abortUnlessCivil($obra);
        abort_unless((int) $import->obra_id === (int) $obra->id, 404);

        $path = $import->original_path;
        $import->delete();

        if ($path && Storage::exists($path)) {
            Storage::delete($path);
        }

        return redirect()
            ->route('obra_civil.insumos.index', $obra)
            ->with('success', 'Carga de insumos eliminada correctamente.');
    }
    public function estimationsIndex(Obra $obra)
    {
        $this->abortUnlessCivil($obra);

        $obra->load('cliente');

        $estimations = CivilEstimation::query()
            ->where('obra_id', $obra->id)
            ->with(['createdBy', 'catalogImport'])
            ->withCount('items')
            ->latest()
            ->get();

        $totals = [
            'count' => $estimations->count(),
            'items' => $estimations->sum('total_items'),
            'quantity' => $estimations->sum(fn ($estimation) => (float) $estimation->total_quantity),
            'subtotal' => $estimations->sum(fn ($estimation) => (float) $estimation->subtotal),
        ];

        return view('obra_civil.estimations.index', compact('obra', 'estimations', 'totals'));
    }

    public function showEstimation(Obra $obra, CivilEstimation $estimation)
    {
        $this->abortUnlessCivil($obra);
        $this->abortUnlessEstimationBelongsToObra($obra, $estimation);

        $obra->load('cliente');
        $estimation->load(['createdBy', 'catalogImport', 'items.concept']);

        return view('obra_civil.estimations.show', compact('obra', 'estimation'));
    }
    public function storeEstimation(Request $request, Obra $obra)
    {
        $this->abortUnlessCivil($obra);

        $activeImport = CivilCatalogImport::query()
            ->where('obra_id', $obra->id)
            ->whereIn('status', ['imported', 'validated'])
            ->latest()
            ->first();

        if (!$activeImport) {
            return back()->with('error', 'Esta obra civil todavia no tiene un catalogo activo para estimar.');
        }

        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.concept_id' => ['required', 'integer', 'distinct', 'exists:civil_concepts,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
        ]);

        $conceptIds = collect($data['items'])->pluck('concept_id')->map(fn ($id) => (int) $id)->values();
        $concepts = CivilConcept::query()
            ->with('partida.building')
            ->whereIn('id', $conceptIds)
            ->whereHas('partida.building', function ($query) use ($activeImport) {
                $query->where('civil_catalog_import_id', $activeImport->id);
            })
            ->get()
            ->keyBy('id');

        if ($concepts->count() !== $conceptIds->unique()->count()) {
            throw ValidationException::withMessages([
                'items' => 'Uno o mas conceptos no pertenecen al catalogo activo de esta obra.',
            ]);
        }

        $balances = app(CivilConceptBalanceService::class)->summaries($conceptIds);

        $lines = [];
        $totalQuantity = 0.0;
        $subtotal = 0.0;

        foreach ($data['items'] as $item) {
            $concept = $concepts->get((int) $item['concept_id']);
            $quantity = round((float) $item['quantity'], 4);
            $budgetQuantity = (float) $concept->budget_quantity;

            $availableQuantity = (float) ($balances->get($concept->id)['available_quantity'] ?? $budgetQuantity);

            if ($quantity > $availableQuantity) {
                throw ValidationException::withMessages([
                    'items' => 'La cantidad de ' . ($concept->excel_code ?: 'un concepto') . ' excede la cantidad disponible por estimar.',
                ]);
            }

            $unitPrice = round((float) $concept->unit_price, 4);
            $amount = round($quantity * $unitPrice, 2);
            $partida = $concept->partida;
            $building = $partida?->building;

            $totalQuantity += $quantity;
            $subtotal += $amount;

            $lines[] = [
                'civil_concept_id' => $concept->id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'amount' => $amount,
                'concept_snapshot' => [
                    'building' => $building?->name,
                    'partida_code' => $partida?->code,
                    'partida_name' => $partida?->name,
                    'excel_code' => $concept->excel_code,
                    'description' => $concept->description,
                    'unit' => $concept->unit,
                    'budget_quantity' => $budgetQuantity,
                    'unit_price' => $unitPrice,
                    'budget_amount' => (float) $concept->budget_amount,
                ],
            ];
        }

        $estimation = DB::transaction(function () use ($obra, $activeImport, $lines, $totalQuantity, $subtotal) {
            $nextNumber = CivilEstimation::query()
                ->where('obra_id', $obra->id)
                ->lockForUpdate()
                ->count() + 1;
            $obraKey = Str::upper(Str::slug($obra->clave_obra ?: (string) $obra->id));

            $estimation = CivilEstimation::create([
                'obra_id' => $obra->id,
                'civil_catalog_import_id' => $activeImport->id,
                'folio' => sprintf('EST-%s-%03d', $obraKey, $nextNumber),
                'status' => 'confirmed',
                'total_items' => count($lines),
                'total_quantity' => round($totalQuantity, 4),
                'subtotal' => round($subtotal, 2),
                'created_by' => auth()->id(),
                'confirmed_at' => now(),
                'metadata' => [
                    'source' => 'obra_civil_details_modal',
                ],
            ]);

            $estimation->items()->createMany($lines);

            return $estimation;
        });

        return redirect()
            ->route('obra_civil.estimations.show', [$obra, $estimation])
            ->with('success', 'Estimacion ' . $estimation->folio . ' guardada correctamente.');
    }
    public function conceptOrders(Obra $obra, CivilConcept $concept)
    {
        $this->abortUnlessCivil($obra);

        $concept->load('partida.building.catalogImport');
        $import = $concept->partida?->building?->catalogImport;

        abort_unless($import && (int) $import->obra_id === (int) $obra->id, 404);

        $balance = app(CivilConceptBalanceService::class)->summary($concept);

        $detalles = DB::table('orden_compra_detalles as d')
            ->join('ordenes_compra as oc', 'oc.id', '=', 'd.orden_compra_id')
            ->leftJoin('proveedores as p', 'p.id', '=', 'oc.proveedor_id')
            ->where('d.civil_concept_id', $concept->id)
            ->select([
                'd.id as detalle_id',
                'd.orden_compra_id',
                'd.cantidad',
                'd.precio_unitario',
                'd.importe',
                'd.iva',
                'd.retenciones',
                'd.otros_impuestos',
                'oc.folio',
                'oc.estado',
                'oc.fecha',
                'p.nombre as proveedor_nombre',
            ])
            ->orderByDesc('oc.id')
            ->orderByDesc('d.id')
            ->get();

        return view('obra_civil.concept_orders', compact('obra', 'concept', 'balance', 'detalles'));
    }

    public function insumoOrders(Obra $obra, ObraCivilInsumo $insumo)
    {
        $this->abortUnlessCivil($obra);
        abort_unless((int) $insumo->obra_id === (int) $obra->id, 404);

        $insumo->load('import');
        $balance = app(ObraCivilInsumoBalanceService::class)->summary($insumo);

        $detalles = DB::table('orden_compra_detalles as d')
            ->join('ordenes_compra as oc', 'oc.id', '=', 'd.orden_compra_id')
            ->leftJoin('proveedores as p', 'p.id', '=', 'oc.proveedor_id')
            ->where('d.obra_civil_insumo_id', $insumo->id)
            ->select([
                'd.id as detalle_id',
                'd.orden_compra_id',
                'd.descripcion',
                'd.unidad',
                'd.cantidad',
                'd.precio_unitario',
                'd.importe',
                'd.iva',
                'd.retenciones',
                'd.otros_impuestos',
                'oc.folio',
                'oc.estado',
                'oc.fecha',
                'p.nombre as proveedor_nombre',
            ])
            ->orderByDesc('oc.id')
            ->orderByDesc('d.id')
            ->get();

        return view('obra_civil.insumos.orders', compact('obra', 'insumo', 'balance', 'detalles'));
    }
    public function destroyCatalog(Obra $obra, CivilCatalogImport $import)
    {
        $this->abortUnlessCivil($obra);
        $this->abortUnlessImportBelongsToObra($obra, $import);

        return $this->deleteCatalogImport($import);
    }

    public function destroyOrphanCatalog(CivilCatalogImport $import)
    {
        if ($import->obra_id !== null && Obra::whereKey($import->obra_id)->exists()) {
            abort(404);
        }

        return $this->deleteCatalogImport($import);
    }

    private function deleteCatalogImport(CivilCatalogImport $import)
    {
        $conceptIds = CivilConcept::query()
            ->whereHas('partida.building', function ($query) use ($import) {
                $query->where('civil_catalog_import_id', $import->id);
            })
            ->pluck('id');

        $detallesVinculados = $conceptIds->isEmpty()
            ? 0
            : DB::table('orden_compra_detalles')
                ->whereIn('civil_concept_id', $conceptIds)
                ->count();

        if ($detallesVinculados > 0) {
            return back()->with(
                'error',
                'No se puede eliminar este catalogo porque ya tiene conceptos usados en ordenes de compra.'
            );
        }

        $path = $import->original_path;

        $import->delete();

        if ($path && Storage::exists($path)) {
            Storage::delete($path);
        }

        return back()->with('success', 'Catalogo civil eliminado correctamente.');
    }
    private function abortUnlessCivil(Obra $obra): void
    {
        abort_unless(in_array(strtoupper((string) $obra->tipo_obra), ['OBRA_CIVIL', 'CIVIL'], true), 404);
    }

    private function abortUnlessEstimationBelongsToObra(Obra $obra, CivilEstimation $estimation): void
    {
        abort_unless((int) $estimation->obra_id === (int) $obra->id, 404);
    }
    private function abortUnlessImportBelongsToObra(Obra $obra, CivilCatalogImport $import): void
    {
        abort_unless((int) $import->obra_id === (int) $obra->id, 404);
    }
}



