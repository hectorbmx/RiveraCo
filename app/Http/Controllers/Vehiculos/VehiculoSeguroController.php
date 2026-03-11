<?php

namespace App\Http\Controllers\Vehiculos;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Vehiculo;
use App\Models\Seguro;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class VehiculoSeguroController extends Controller
{

    public function create(Vehiculo $vehiculo)
    {
        return view('vehiculos.seguros.create', compact('vehiculo'));
    }


public function store(Request $request, Vehiculo $vehiculo)
{
    $validated = $request->validate([
        'aseguradora' => 'required|string|max:255',
        'poliza_numero' => 'required|string|max:255',
        'tipo_seguro' => 'nullable|string|max:100',
        'metodo_pago' => 'nullable|string|max:100',
        'costo' => 'nullable|numeric|min:0',
        'moneda' => 'nullable|string|max:10',
        'fecha_compra' => 'nullable|date',
        'vigencia_desde' => 'required|date',
        'vigencia_hasta' => 'required|date|after_or_equal:vigencia_desde',
        'suma_asegurada' => 'nullable|numeric|min:0',
        'deducible' => 'nullable|numeric|min:0',
        'cobertura' => 'nullable|string',
        'observaciones' => 'nullable|string',
        'documento' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
        'comprobante' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
    ]);

    $rutaDocumento = null;
    $rutaComprobante = null;

    if ($request->hasFile('documento')) {
        $rutaDocumento = $request->file('documento')->store('seguros/documentos', 'public');
    }

    if ($request->hasFile('comprobante')) {
        $rutaComprobante = $request->file('comprobante')->store('seguros/comprobantes', 'public');
    }

    $hoy = now()->toDateString();

    if ($validated['vigencia_desde'] > $hoy) {
        $estatus = 'futura';
    } elseif ($validated['vigencia_hasta'] < $hoy) {
        $estatus = 'vencida';
    } else {
        $estatus = 'vigente';
    }

    $vehiculo->seguros()->create([
        'aseguradora' => $validated['aseguradora'],
        'poliza_numero' => $validated['poliza_numero'],
        'tipo_seguro' => $validated['tipo_seguro'] ?? null,
        'metodo_pago' => $validated['metodo_pago'] ?? null,
        'costo' => $validated['costo'] ?? 0,
        'moneda' => $validated['moneda'] ?? 'MXN',
        'fecha_compra' => $validated['fecha_compra'] ?? null,
        'vigencia_desde' => $validated['vigencia_desde'],
        'vigencia_hasta' => $validated['vigencia_hasta'],
        'suma_asegurada' => $validated['suma_asegurada'] ?? null,
        'deducible' => $validated['deducible'] ?? null,
        'cobertura' => $validated['cobertura'] ?? null,
        'estatus' => $estatus,
        'documento_path' => $rutaDocumento,
        'comprobante_path' => $rutaComprobante,
        'observaciones' => $validated['observaciones'] ?? null,
        'created_by' => auth()->id(),
        'updated_by' => auth()->id(),
    ]);

    return redirect()
        ->route('mantenimiento.vehiculos.edit', ['vehiculo' => $vehiculo->id, 'tab' => 'seguro'])
        ->with('success', 'Póliza registrada correctamente.');
}

    public function edit(Vehiculo $vehiculo, Seguro $seguro)
    {
        // Validar que el seguro sí pertenece al vehículo
        if (
            $seguro->asegurable_type !== $vehiculo->getMorphClass() ||
            $seguro->asegurable_id != $vehiculo->id
        ) {
            abort(404);
        }

        return view('vehiculos.seguros.edit', compact('vehiculo', 'seguro'));
    }

public function update(Request $request, Vehiculo $vehiculo, Seguro $seguro)
{
    if (
        $seguro->asegurable_type !== $vehiculo->getMorphClass() ||
        $seguro->asegurable_id != $vehiculo->id
    ) {
        abort(404);
    }

    $validated = $request->validate([
        'aseguradora' => 'required|string|max:255',
        'poliza_numero' => 'required|string|max:255',
        'tipo_seguro' => 'nullable|string|max:100',
        'metodo_pago' => 'nullable|string|max:100',
        'costo' => 'nullable|numeric|min:0',
        'moneda' => 'nullable|string|max:10',
        'fecha_compra' => 'nullable|date',
        'vigencia_desde' => 'required|date',
        'vigencia_hasta' => 'required|date|after_or_equal:vigencia_desde',
        'suma_asegurada' => 'nullable|numeric|min:0',
        'deducible' => 'nullable|numeric|min:0',
        'cobertura' => 'nullable|string',
        'observaciones' => 'nullable|string',
        'documento' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
        'comprobante' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
    ]);

    DB::transaction(function () use ($request, $validated, $seguro) {
        $seguro->aseguradora = $validated['aseguradora'];
        $seguro->poliza_numero = $validated['poliza_numero'];
        $seguro->tipo_seguro = $validated['tipo_seguro'] ?? null;
        $seguro->metodo_pago = $validated['metodo_pago'] ?? null;
        $seguro->costo = $validated['costo'] ?? 0;
        $seguro->moneda = $validated['moneda'] ?? 'MXN';
        $seguro->fecha_compra = $validated['fecha_compra'] ?? null;
        $seguro->vigencia_desde = $validated['vigencia_desde'];
        $seguro->vigencia_hasta = $validated['vigencia_hasta'];
        $seguro->suma_asegurada = $validated['suma_asegurada'] ?? null;
        $seguro->deducible = $validated['deducible'] ?? null;
        $seguro->cobertura = $validated['cobertura'] ?? null;
        $seguro->observaciones = $validated['observaciones'] ?? null;

        $hoy = now()->toDateString();

        if ($validated['vigencia_desde'] > $hoy) {
            $seguro->estatus = 'futura';
        } elseif ($validated['vigencia_hasta'] < $hoy) {
            $seguro->estatus = 'vencida';
        } else {
            $seguro->estatus = 'vigente';
        }

        if ($request->hasFile('documento')) {
            if ($seguro->documento_path && Storage::disk('public')->exists($seguro->documento_path)) {
                Storage::disk('public')->delete($seguro->documento_path);
            }

            $seguro->documento_path = $request->file('documento')
                ->store('seguros/documentos', 'public');
        }

        if ($request->hasFile('comprobante')) {
            if ($seguro->comprobante_path && Storage::disk('public')->exists($seguro->comprobante_path)) {
                Storage::disk('public')->delete($seguro->comprobante_path);
            }

            $seguro->comprobante_path = $request->file('comprobante')
                ->store('seguros/comprobantes', 'public');
        }

        $seguro->updated_by = auth()->id();
        $seguro->save();
    });

    return redirect()
        ->route('mantenimiento.vehiculos.edit', ['vehiculo' => $vehiculo->id, 'tab' => 'seguro'])
        ->with('success', 'Póliza actualizada correctamente.');
}

}