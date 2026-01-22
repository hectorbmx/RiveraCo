<?php

namespace App\Http\Controllers;

use App\Models\Obra;
use App\Models\ObraPresupuesto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ObraPresupuestoController extends Controller
{
    public function store(Request $request, Obra $obra)
    {
        $data = $request->validate([
            'nombre'  => ['required', 'string', 'max:255'],
            'version' => ['nullable', 'string', 'max:100'],
            'fecha'   => ['nullable', 'date'],
            'notas'   => ['nullable', 'string'],
            'archivo' => ['required', 'file', 'mimes:pdf', 'max:10240'], // 10MB
        ]);

        $path = $request->file('archivo')->store('presupuestos', 'public');

        $data['archivo_path'] = $path;
        $data['obra_id'] = $obra->id;

        ObraPresupuesto::create($data);

        return redirect()
            ->route('obras.edit', ['obra' => $obra->id, 'tab' => 'presupuestos'])
            ->with('success', 'Presupuesto cargado correctamente.');
    }

    public function destroy(Obra $obra, ObraPresupuesto $presupuesto)
    {
        if ($presupuesto->obra_id !== $obra->id) {
            abort(404);
        }

        if ($presupuesto->archivo_path && Storage::disk('public')->exists($presupuesto->archivo_path)) {
            Storage::disk('public')->delete($presupuesto->archivo_path);
        }

        $presupuesto->delete();

        return redirect()
            ->route('obras.edit', ['obra' => $obra->id, 'tab' => 'presupuestos'])
            ->with('success', 'Presupuesto eliminado correctamente.');
    }
}
