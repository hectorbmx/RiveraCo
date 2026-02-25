<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Factura;

class FacturaController extends Controller
{
    public function index(Request $request)
    {
        $q = Factura::query()
            ->select([
                'id',
                'source_system',
                'uuid','serie','folio','tipo_comprobante',
                'rfc_emisor','rfc_receptor','razon_social',
                'fecha_emision','fecha_timbrado',
                'moneda','tipo_cambio',
                'subtotal','descuento',
                'iva_0','iva_8','iva_16','ieps','iva_retenido','isr_retenido',
                'total',
                'status',
                'fecha_cancelacion',
                // si tu columna xml es grande, puedes dejarla fuera y cargarla solo al ver detalle
                'xml',
            ])
            ->orderByDesc('fecha_emision');

        // 🔎 Búsqueda general
        if ($request->filled('q')) {
            $term = trim((string) $request->q);
            $q->where(function ($x) use ($term) {
                $x->where('uuid', 'like', "%{$term}%")
                  ->orWhere('rfc_emisor', 'like', "%{$term}%")
                  ->orWhere('rfc_receptor', 'like', "%{$term}%")
                  ->orWhere('razon_social', 'like', "%{$term}%")
                  ->orWhere('serie', 'like', "%{$term}%")
                  ->orWhere('folio', 'like', "%{$term}%");
            });
        }

        // ✅ Estatus
        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }

        // ✅ Source system
        if ($request->filled('source_system')) {
            $q->where('source_system', $request->source_system);
        }

        // 📅 Fechas (emisión)
        if ($request->filled('from')) {
            $q->whereDate('fecha_emision', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $q->whereDate('fecha_emision', '<=', $request->to);
        }

        // Paginación
        $facturas = $q->paginate(25)->withQueryString();

        // Para combos
        $statuses = Factura::query()
            ->select('status')
            ->whereNotNull('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status');

        $sources = Factura::query()
            ->select('source_system')
            ->whereNotNull('source_system')
            ->distinct()
            ->orderBy('source_system')
            ->pluck('source_system');

        return view('facturas.index', compact('facturas', 'statuses', 'sources'));
    }
}