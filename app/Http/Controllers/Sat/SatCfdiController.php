<?php

namespace App\Http\Controllers\Sat;

use App\Http\Controllers\Controller;
use App\Models\SatCfdi;
use App\Models\SatEmpresa;
use Illuminate\Http\Request;
use App\Models\Obra;
use App\Models\OrdenCompra;

use Barryvdh\DomPDF\Facade\Pdf;

class SatCfdiController extends Controller
{
    public function index(Request $request)
    {
        $empresas = SatEmpresa::where('activo', true)
            ->orderBy('nombre')
            ->get();

        $empresaSeleccionada = null;
        $obras = Obra::orderBy('nombre')->get();
        $ordenesCompra = OrdenCompra::with('proveedor')
        ->orderByDesc('id')
        ->get();
        $cfdis = collect();

        $totalGeneral = 0;
        $totalFiltrado = 0;

        $resumenIngresos = 0;
        $resumenEgresos = 0;
        $resumenPagos = 0;

        $subtotalIngresos = 0;
        $subtotalEgresos = 0;
        $subtotalPagos = 0;

        // Si no hay empresa seleccionada, no cargamos nada
        if (! $request->filled('sat_empresa_id')) {
            return view('sat.cfdis.index', compact(
                'empresas',
                'empresaSeleccionada',
                'cfdis',
                'totalGeneral',
                'totalFiltrado',
                'resumenIngresos',
                'resumenEgresos',
                'resumenPagos',
                'subtotalIngresos',
                'subtotalEgresos',
                'subtotalPagos',
                'obras',
                'ordenesCompra'
            ));
        }

        $empresaSeleccionada = SatEmpresa::find($request->sat_empresa_id);

        if (! $empresaSeleccionada) {
            return view('sat.cfdis.index', compact(
                'empresas',
                'empresaSeleccionada',
                'cfdis',
                'totalGeneral',
                'totalFiltrado',
                'resumenIngresos',
                'resumenEgresos',
                'resumenPagos',
                'subtotalIngresos',
                'subtotalEgresos',
                'subtotalPagos',
                'obras',
                'ordenesCompra'
            ));
        }

        // $q = SatCfdi::query()->where(function ($sub) use ($empresaSeleccionada) {
        // $q = SatCfdi::query()->with('obra')->where(function ($sub) use ($empresaSeleccionada) {
        $q = SatCfdi::query()->with(['obra', 'ordenCompra'])->where(function ($sub) use ($empresaSeleccionada) {
            $sub->where('rfc_emisor', $empresaSeleccionada->rfc)
                ->orWhere('rfc_receptor', $empresaSeleccionada->rfc);
        });

        // Total de esa empresa antes de filtros adicionales
        $totalGeneral = (clone $q)->count();

        // Filtros adicionales
        if ($request->filled('uuid')) {
            $q->where('uuid', 'like', '%' . $request->uuid . '%');
        }

        if ($request->filled('rfc_emisor')) {
            $q->where('rfc_emisor', 'like', '%' . $request->rfc_emisor . '%');
        }

        if ($request->filled('emisor_nombre')) {
            $q->where('emisor_nombre', 'like', '%' . $request->emisor_nombre . '%');
        }

        if ($request->filled('rfc_receptor')) {
            $q->where('rfc_receptor', 'like', '%' . $request->rfc_receptor . '%');
        }

        if ($request->filled('receptor_nombre')) {
            $q->where('receptor_nombre', 'like', '%' . $request->receptor_nombre . '%');
        }

        if ($request->filled('tipo_comprobante')) {
            $q->where('tipo_comprobante', $request->tipo_comprobante);
        }

        if ($request->filled('fecha_inicio')) {
            $q->whereDate('fecha_emision', '>=', $request->fecha_inicio);
        }

        if ($request->filled('fecha_fin')) {
            $q->whereDate('fecha_emision', '<=', $request->fecha_fin);
        }

        $totalFiltrado = (clone $q)->count();

        $resumenIngresos = (clone $q)->where('tipo_comprobante', 'I')->count();
        $resumenEgresos = (clone $q)->where('tipo_comprobante', 'E')->count();
        $resumenPagos = (clone $q)->where('tipo_comprobante', 'P')->count();
        $resumenNominas = (clone $q)->where('tipo_comprobante', 'N')->count();

        $subtotalIngresos = (clone $q)->where('tipo_comprobante', 'I')->sum('total');
        $subtotalEgresos = (clone $q)->where('tipo_comprobante', 'E')->sum('total');
        $subtotalPagos = (clone $q)->where('tipo_comprobante', 'P')->sum('total');
        $subtotalNominas = (clone $q)->where('tipo_comprobante', 'N')->sum('total');

        $cfdis = $q->orderByDesc('fecha_emision')
            ->paginate(20)
            ->withQueryString();
        
        return view('sat.cfdis.index', compact(
            'empresas',
            'empresaSeleccionada',
            'cfdis',
            'totalGeneral',
            'totalFiltrado',
            'resumenIngresos',
            'resumenEgresos',
            'resumenPagos',
            'resumenNominas',
            'subtotalIngresos',
            'subtotalEgresos',
            'subtotalPagos',
            'subtotalNominas',
            'obras',
            'ordenesCompra'
        ));
    }
    public function show(\App\Models\SatCfdi $cfdi)
    {
        $cfdi->load('conceptos');

        return view('sat.cfdis.show', compact('cfdi'));
    }
    public function detalle(\App\Models\SatCfdi $cfdi)
        {
            $cfdi->load('conceptos');

            return response()->json([
                'cfdi' => $cfdi,
            ]);
        }

 public function relacionar(Request $request, SatCfdi $cfdi)
{
    $rfcEmpresa = 'RCO820921T66';

    if ($cfdi->rfc_emisor === $rfcEmpresa) {
        $validated = $request->validate([
            'obra_id' => ['required', 'exists:obras,id'],
        ]);

        $cfdi->update([
            'obra_id' => $validated['obra_id'],
            'orden_compra_id' => null,
        ]);

        return redirect()
            ->back()
            ->with('success', 'CFDI relacionado correctamente a la obra.');
    }

    $validated = $request->validate([
        'orden_compra_id' => ['required', 'exists:ordenes_compra,id'],
    ]);

    $cfdi->update([
        'orden_compra_id' => $validated['orden_compra_id'],
        'obra_id' => null,
    ]);

    return redirect()
        ->back()
        ->with('success', 'CFDI relacionado correctamente a la orden de compra.');
}

public function pdf(SatCfdi $cfdi)
{
    if (!$cfdi->xml_path || !file_exists($cfdi->xml_path)) {
        abort(404, 'No se encontró el XML físico del CFDI.');
    }

    $xmlString = file_get_contents($cfdi->xml_path);
    $xmlString = preg_replace('/^\xEF\xBB\xBF/', '', $xmlString);

    $xml = simplexml_load_string($xmlString);

    $xml->registerXPathNamespace('cfdi', 'http://www.sat.gob.mx/cfd/4');
    $xml->registerXPathNamespace('tfd', 'http://www.sat.gob.mx/TimbreFiscalDigital');

    $emisor = $xml->xpath('//cfdi:Emisor')[0] ?? null;
    $receptor = $xml->xpath('//cfdi:Receptor')[0] ?? null;
    $conceptos = $xml->xpath('//cfdi:Concepto') ?? [];
    $timbre = $xml->xpath('//tfd:TimbreFiscalDigital')[0] ?? null;

    $pdf = Pdf::loadView('sat.cfdis.pdf', compact(
        'cfdi',
        'xml',
        'emisor',
        'receptor',
        'conceptos',
        'timbre'
    ));

    return $pdf->stream("CFDI-{$cfdi->uuid}.pdf");
}

}
