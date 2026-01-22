<?php

namespace App\Http\Controllers;

use App\Models\Obra;
use App\Models\ObraContrato;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ObraContratoController extends Controller
{
    public function store(Request $request, Obra $obra)
    {
        $data = $request->validate([
            'tipo'           => ['nullable', 'string', 'max:100'],
            'nombre'         => ['nullable', 'string', 'max:255'],
            'descripcion'    => ['nullable', 'string'],
            'monto_contrato' => ['nullable', 'numeric'],
            'fecha_firma'    => ['nullable', 'date'],
            'archivo'        => ['required', 'file', 'mimes:pdf', 'max:5120'], // 5MB
        ]);

        // Guardar archivo en storage/app/public/contratos
        $path = $request->file('archivo')->store('contratos', 'public');

        $data['archivo_path'] = $path;
        $data['obra_id'] = $obra->id;

        ObraContrato::create($data);

        return redirect()
            ->route('obras.edit', ['obra' => $obra->id, 'tab' => 'contratos'])
            ->with('success', 'Contrato cargado correctamente.');
    }

    public function destroy(Obra $obra, ObraContrato $contrato)
    {
        // Seguridad extra: asegurarnos de que el contrato pertenece a la obra
        if ($contrato->obra_id !== $obra->id) {
            abort(404);
        }

        // Borrar archivo
        if ($contrato->archivo_path && Storage::disk('public')->exists($contrato->archivo_path)) {
            Storage::disk('public')->delete($contrato->archivo_path);
        }

        $contrato->delete();

        return redirect()
            ->route('obras.edit', ['obra' => $obra->id, 'tab' => 'contratos'])
            ->with('success', 'Contrato eliminado correctamente.');
    }
        public function edit(Request $request, Obra $obra)
    {
        $clientes      = Cliente::orderBy('nombre_comercial')->get();
        $responsables  = User::orderBy('name')->get();

        $obra->load('cliente', 'contratos');

        $tab = $request->query('tab', 'general');

        return view('obras.edit', compact('obra', 'clientes', 'responsables', 'tab'));
    }

}
