<?php

namespace App\Http\Controllers;

use App\Models\Obra;
use App\Models\ObraFactura;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ObraFacturaController extends Controller
{
    // Guardar una nueva factura para una obra
    public function store(Request $request, Obra $obra)
    {
        $data = $request->validate([
            'fecha_factura' => ['required', 'date'],
            'fecha_pago'    => ['nullable', 'date', 'after_or_equal:fecha_factura'],
            'monto'         => ['required', 'numeric', 'min:0'],
            'pdf'           => ['nullable', 'file', 'mimes:pdf', 'max:5120'], // máx 5MB
            'notas'         => ['nullable', 'string'],
        ]);

        $pdfPath = null;

        if ($request->hasFile('pdf')) {
            // Guarda en storage/app/public/obras-facturas/{obra_id}
            $pdfPath = $request->file('pdf')
                ->store("obras-facturas/{$obra->id}", 'public');
        }

        $obra->facturas()->create([
            'fecha_factura' => $data['fecha_factura'],
            'fecha_pago'    => $data['fecha_pago'] ?? null,
            'monto'         => $data['monto'],
            'pdf_path'      => $pdfPath,
            'notas'         => $data['notas'] ?? null,
        ]);

        return redirect()
            ->route('obras.edit', ['obra' => $obra, 'tab' => 'facturacion'])
            ->with('success', 'Factura registrada correctamente.');
    }

    // Eliminar una factura
    public function destroy(Obra $obra, ObraFactura $factura)
    {
        // Seguridad básica: que la factura pertenezca a esta obra
        if ($factura->obra_id !== $obra->id) {
            abort(404);
        }

        // Borrar PDF si existe
        if ($factura->pdf_path) {
            Storage::disk('public')->delete($factura->pdf_path);
        }

        $factura->delete();

        return redirect()
            ->route('obras.edit', ['obra' => $obra, 'tab' => 'facturacion'])
            ->with('success', 'Factura eliminada correctamente.');
    }
     public function marcarPagada(Request $request, Obra $obra, ObraFactura $factura)
    {
        // Seguridad: verificar que la factura pertenezca a la obra
        if ($factura->obra_id !== $obra->id) {
            abort(404);
        }

        // Si quieres permitir capturar fecha_pago, puedes validar aquí.
        // Por ahora, si no envían fecha, usamos hoy.
        $data = $request->validate([
            'fecha_pago' => ['nullable', 'date'],
        ]);

        $fechaPago = $data['fecha_pago'] ?? now()->toDateString();

        $factura->update([
            'estado'     => 'pagada',
            'fecha_pago' => $fechaPago,
        ]);

        return redirect()
            ->route('obras.edit', ['obra' => $obra, 'tab' => 'facturacion'])
            ->with('success', 'Factura marcada como pagada.');
    }

    public function marcarCancelada(Obra $obra, ObraFactura $factura)
    {
        if ($factura->obra_id !== $obra->id) {
            abort(404);
        }

        $factura->update([
            'estado'     => 'cancelada',
            'fecha_pago' => null,
        ]);

        return redirect()
            ->route('obras.edit', ['obra' => $obra, 'tab' => 'facturacion'])
            ->with('success', 'Factura marcada como cancelada.');
    }

}
