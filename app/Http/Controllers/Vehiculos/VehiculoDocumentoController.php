<?php

namespace App\Http\Controllers\Vehiculos;

use App\Http\Controllers\Controller;
use App\Models\Vehiculo;
use App\Models\VehiculoDocumento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class VehiculoDocumentoController extends Controller
{
public function store(Request $request, Vehiculo $vehiculo)
{
    $data = $request->validate([
        'archivo' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        'fecha_documento' => ['nullable', 'date'],
        'fecha_vencimiento' => ['nullable', 'date'],
        'observaciones' => ['nullable', 'string'],
    ], [
        'archivo.required' => 'Debes seleccionar un archivo.',
        'archivo.mimes' => 'El archivo debe ser PDF o imagen (jpg, jpeg, png, webp).',
        'archivo.max' => 'El archivo no debe exceder 10 MB.',
    ]);

    $tipo = 'tarjeta_circulacion';

    try {
        DB::transaction(function () use ($request, $vehiculo, $data, $tipo) {
            $archivo = $request->file('archivo');

            $path = $archivo->store(
                'vehiculos/' . $vehiculo->id . '/documentos',
                'public'
            );

            VehiculoDocumento::where('vehiculo_id', $vehiculo->id)
                ->where('tipo', $tipo)
                ->where('vigente', true)
                ->update(['vigente' => false]);

            VehiculoDocumento::create([
                'vehiculo_id' => $vehiculo->id,
                'tipo' => $tipo,
                'nombre_original' => $archivo->getClientOriginalName(),
                'archivo_path' => $path,
                'mime_type' => $archivo->getMimeType(),
                'tamano' => $archivo->getSize(),
                'fecha_documento' => $data['fecha_documento'] ?? null,
                'fecha_vencimiento' => $data['fecha_vencimiento'] ?? null,
                'vigente' => true,
                'observaciones' => $data['observaciones'] ?? null,
            ]);
        });

        return redirect()
            ->route('mantenimiento.vehiculos.edit', $vehiculo)
            ->with('success', 'Documento cargado correctamente.');
    } catch (\Throwable $e) {
        return redirect()
            ->route('mantenimiento.vehiculos.edit', $vehiculo)
            ->withErrors(['documento' => 'Ocurrió un error al guardar el documento: ' . $e->getMessage()])
            ->withInput();
    }
}

    public function destroy(Vehiculo $vehiculo, VehiculoDocumento $documento)
    {
        try {
            if ((int) $documento->vehiculo_id !== (int) $vehiculo->id) {
                return redirect()
                    ->route('mantenimiento.vehiculos.edit', $vehiculo->id)
                    ->withErrors(['documento' => 'El documento no pertenece a este vehículo.']);
            }

            DB::transaction(function () use ($documento) {
                if (!empty($documento->archivo_path) && Storage::disk('public')->exists($documento->archivo_path)) {
                    Storage::disk('public')->delete($documento->archivo_path);
                }

                $documento->delete();
            });

            return redirect()
                ->route('mantenimiento.vehiculos.edit', $vehiculo->id)
                ->with('success', 'Documento eliminado correctamente.');
        } catch (\Throwable $e) {
            return redirect()
                ->route('mantenimiento.vehiculos.edit', $vehiculo->id)
                ->withErrors(['documento' => 'Ocurrió un error al eliminar el documento: ' . $e->getMessage()]);
        }
    }
}