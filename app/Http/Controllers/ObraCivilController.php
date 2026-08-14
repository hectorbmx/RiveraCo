<?php

namespace App\Http\Controllers;

use App\Models\CivilCatalogImport;
use App\Models\CivilConcept;
use App\Models\Obra;
use App\Services\CivilCatalogExcelParser;
use App\Services\CivilConceptBalanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ObraCivilController extends Controller
{
    public function index()
    {
        $obras = Obra::query()
            ->whereIn('tipo_obra', ['OBRA_CIVIL', 'CIVIL'])
            ->with('cliente')
            ->withCount('civilCatalogImports')
            ->orderBy('nombre')
            ->get();

        $catalogImports = CivilCatalogImport::query()
            ->with(['obra', 'importedBy'])
            ->latest()
            ->limit(8)
            ->get();

        $stats = [
            'obras' => $obras->count(),
            'catalogos' => CivilCatalogImport::count(),
            'conceptos' => CivilConcept::count(),
            'importe' => (float) CivilConcept::sum('budget_amount'),
        ];

        return view('obra_civil.index', compact('obras', 'catalogImports', 'stats'));
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

        $balances = collect();
        $movementCounts = collect();

        if ($activeImport) {
            $conceptIds = $activeImport->buildings
                ->flatMap(fn ($building) => $building->partidas)
                ->flatMap(fn ($partida) => $partida->concepts)
                ->pluck('id');

            $balances = app(CivilConceptBalanceService::class)->summaries($conceptIds);
            $movementCounts = DB::table('orden_compra_detalles')
                ->whereIn('civil_concept_id', $conceptIds)
                ->selectRaw('civil_concept_id, COUNT(DISTINCT orden_compra_id) as orders_count')
                ->groupBy('civil_concept_id')
                ->pluck('orders_count', 'civil_concept_id');
        }

        return view('obra_civil.details', compact('obra', 'imports', 'activeImport', 'drafts', 'balances', 'movementCounts'));
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
    public function destroyCatalog(Obra $obra, CivilCatalogImport $import)
    {
        $this->abortUnlessCivil($obra);
        $this->abortUnlessImportBelongsToObra($obra, $import);

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

    private function abortUnlessImportBelongsToObra(Obra $obra, CivilCatalogImport $import): void
    {
        abort_unless((int) $import->obra_id === (int) $obra->id, 404);
    }
}
