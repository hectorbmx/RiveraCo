<?php

namespace App\Http\Controllers\Sat;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SatEmpresa;
use App\Models\SatCfdi;
use Illuminate\Support\Facades\DB;

class SatCfdiEstadisticaController extends Controller
{
    //
   public function index(SatEmpresa $empresa)
{
    $empresaRfc = $empresa->rfc;
    $year = (int) request('year', now()->year);

    $years = DB::table('sat_cfdis')
    ->selectRaw('YEAR(fecha_emision) as year')
    ->whereNotNull('fecha_emision')
    ->where(function ($q) use ($empresaRfc) {
        $q->where('rfc_emisor', $empresaRfc)
          ->orWhere('rfc_receptor', $empresaRfc);
    })
    ->groupByRaw('YEAR(fecha_emision)')
    ->orderByDesc('year')
    ->pluck('year');

    $ingresos = DB::table('sat_cfdis')
        ->selectRaw('MONTH(fecha_emision) as mes, SUM(total) as total')
        ->whereYear('fecha_emision', $year)
        ->where('rfc_emisor', $empresaRfc)
        ->groupBy('mes')
        ->pluck('total', 'mes');

    $gastos = DB::table('sat_cfdis')
        ->selectRaw('MONTH(fecha_emision) as mes, SUM(total) as total')
        ->whereYear('fecha_emision', $year)
        ->where('rfc_receptor', $empresaRfc)
        ->groupBy('mes')
        ->pluck('total', 'mes');

       

    $meses = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];

    $dataIngresos = [];
    $dataGastos = [];

    for ($i = 1; $i <= 12; $i++) {
        $dataIngresos[] = (float) ($ingresos[$i] ?? 0);
        $dataGastos[] = (float) ($gastos[$i] ?? 0);
    }
 $totalIngresos = array_sum($dataIngresos);
        $totalGastos = array_sum($dataGastos);
        $balance = $totalIngresos - $totalGastos;

        $totalCfdisIngresos = DB::table('sat_cfdis')
            ->whereYear('fecha_emision', $year)
            ->where('rfc_emisor', $empresaRfc)
            ->count();

        $totalCfdisGastos = DB::table('sat_cfdis')
            ->whereYear('fecha_emision', $year)
            ->where('rfc_receptor', $empresaRfc)
            ->count();
        $topClientes = DB::table('sat_cfdis')
            ->selectRaw('
                rfc_receptor as rfc,
                receptor_nombre as nombre,
                SUM(total) as total
            ')
            ->whereYear('fecha_emision', $year)
            ->where('rfc_emisor', $empresaRfc)
            ->groupBy('rfc_receptor', 'receptor_nombre')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $topProveedores = DB::table('sat_cfdis')
            ->selectRaw('
                rfc_emisor as rfc,
                emisor_nombre as nombre,
                SUM(total) as total
            ')
            ->whereYear('fecha_emision', $year)
            ->where('rfc_receptor', $empresaRfc)
            ->groupBy('rfc_emisor', 'emisor_nombre')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

$topClientesLabels = $topClientes->map(fn ($item) => $item->nombre ?: $item->rfc)->values();
$topClientesData = $topClientes->map(fn ($item) => (float) $item->total)->values();

$topProveedoresLabels = $topProveedores->map(fn ($item) => $item->nombre ?: $item->rfc)->values();
$topProveedoresData = $topProveedores->map(fn ($item) => (float) $item->total)->values();
    return view('sat.cfdis.estadisticas.index', compact(
        'year',
        'years',
        'empresa',
        'empresaRfc',
        'meses',
        'dataIngresos',
        'dataGastos',
        'totalIngresos',
        'totalGastos',
        'balance',
        'totalCfdisIngresos',
        'totalCfdisGastos',
        'topClientesLabels',
        'topClientesData',
        'topProveedoresLabels',
        'topProveedoresData',
    ));
}

public function detalleMes(Request $request, $empresa)
{
    $empresa = SatEmpresa::findOrFail($empresa);

    $year = (int) $request->input('year', now()->year);
    $month = (int) $request->input('month');
    $tipo = $request->input('tipo'); // ingresos | gastos

    if ($month < 1 || $month > 12) {
        return response()->json([
            'message' => 'Mes inválido.',
            'items' => [],
            'subtotal' => 0,
            'total' => 0,
        ], 422);
    }

    if (! in_array($tipo, ['ingresos', 'gastos'], true)) {
        return response()->json([
            'message' => 'Tipo inválido.',
            'items' => [],
            'subtotal' => 0,
            'total' => 0,
        ], 422);
    }

    $rfc = strtoupper(trim($empresa->rfc));

    $query = SatCfdi::query()
        ->whereYear('fecha_emision', $year)
        ->whereMonth('fecha_emision', $month)
        ->where('tipo_comprobante', 'I');

    if ($tipo === 'ingresos') {
        $query->where('emisor_rfc', $rfc);
    }

    if ($tipo === 'gastos') {
        $query->where('receptor_rfc', $rfc);
    }

    $cfdis = $query
        ->orderBy('fecha_emision', 'desc')
        ->get();

    $items = $cfdis->map(function ($cfdi) use ($tipo) {
        $serieFolio = trim(($cfdi->serie ?? '') . ' ' . ($cfdi->folio ?? ''));

        return [
            'id' => $cfdi->id,
            'fecha' => $cfdi->fecha_emision
                ? \Carbon\Carbon::parse($cfdi->fecha_emision)->format('Y-m-d')
                : null,

            'uuid' => $cfdi->uuid,
            'serie_folio' => $serieFolio !== '' ? $serieFolio : null,

            'rfc' => $tipo === 'ingresos'
                ? $cfdi->receptor_rfc
                : $cfdi->emisor_rfc,

            'nombre' => $tipo === 'ingresos'
                ? $cfdi->receptor_nombre
                : $cfdi->emisor_nombre,

            'subtotal' => (float) $cfdi->subtotal,
            'total' => (float) $cfdi->total,

            'url' => route('sat.cfdis.show', $cfdi->id),
        ];
    });

    return response()->json([
        'empresa_id' => $empresa->id,
        'empresa_rfc' => $rfc,
        'year' => $year,
        'month' => $month,
        'tipo' => $tipo,
        'count' => $cfdis->count(),
        'subtotal' => (float) $cfdis->sum('subtotal'),
        'total' => (float) $cfdis->sum('total'),
        'items' => $items,
    ]);
}
}
