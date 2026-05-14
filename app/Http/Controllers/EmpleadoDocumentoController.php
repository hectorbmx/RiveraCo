<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\EmpleadoDocumento;
use App\Models\EmpresaDocumentoTipo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EmpleadoDocumentoController extends Controller
{
    public function store(Request $request, Empleado $empleado)
    {
        $data = $request->validate([
            // 'tipo_documento'     => ['required', 'in:' . implode(',', EmpleadoDocumento::TIPOS)],
            'documento_tipo_id' => ['required', 'exists:empresa_documento_tipos,id'],
            'nombre_documento'   => ['nullable', 'string', 'max:255'],
            'fecha_documento'    => ['nullable', 'date'],
            'fecha_vencimiento'  => ['nullable', 'date', 'after_or_equal:fecha_documento'],
            'observaciones'      => ['nullable', 'string'],
            'archivo'            => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'], // 10 MB
        ], [
            'documento_tipo_id.required' => 'Selecciona el tipo de documento.',
            'documento_tipo_id.exists'   => 'El documento seleccionado no es válido.',
            'fecha_vencimiento.after_or_equal' => 'La fecha de vencimiento no puede ser menor a la fecha del documento.',
            'archivo.required'           => 'Debes seleccionar un archivo.',
            'archivo.file'               => 'El archivo seleccionado no es válido.',
            'archivo.mimes'              => 'El archivo debe ser PDF, JPG, JPEG, PNG o WEBP.',
            'archivo.max'                => 'El archivo no debe pesar más de 10 MB.',
        ]);
      $documentoTipo = EmpresaDocumentoTipo::findOrFail($data['documento_tipo_id']);

DB::transaction(function () use ($request, $empleado, $data, $documentoTipo) {

    $file = $request->file('archivo');

    $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
    $originalName = $file->getClientOriginalName();

    $safeTipo = Str::slug($documentoTipo->codigo, '_');
    $filename = $safeTipo . '_' . now()->format('Ymd_His') . '_' . Str::random(8) . '.' . $extension;

    $directory = 'empleados/' . $empleado->id_Empleado . '/documentos';
    $path = $file->storeAs($directory, $filename, 'public');

    EmpleadoDocumento::where('empleado_id', $empleado->id_Empleado)
        ->where('documento_tipo_id', $documentoTipo->id)
        ->where('vigente', true)
        ->update([
            'vigente' => false,
            'updated_by' => auth()->id(),
            'updated_at' => now(),
        ]);

    EmpleadoDocumento::create([
        'empleado_id'              => $empleado->id_Empleado,
        'documento_tipo_id'        => $documentoTipo->id,
        'tipo_documento'           => $documentoTipo->codigo,
        'nombre_documento'         => $data['nombre_documento'] ?? null,
        'archivo_path'             => $path,
        'archivo_nombre_original'  => $originalName,
        'mime_type'                => $file->getMimeType(),
        'extension'                => $extension,
        'tamano_bytes'             => $file->getSize(),
        'fecha_documento'          => $data['fecha_documento'] ?? null,
        'fecha_vencimiento'        => $data['fecha_vencimiento'] ?? null,
        'vigente'                  => true,
        'estatus_validacion'       => 'pendiente',
        'validado_por'             => null,
        'validado_en'              => null,
        'observaciones'            => $data['observaciones'] ?? null,
        'created_by'               => auth()->id(),
        'updated_by'               => auth()->id(),
    ]);
});
        return redirect()
            ->route('empleados.edit', ['empleado' => $empleado->id_Empleado, 'tab' => 'documentos'])
            ->with('success', 'Documento guardado correctamente.');
    }

    //borrar decous
    public function destroy(Empleado $empleado, EmpleadoDocumento $documento)
{
    if ((int) $documento->empleado_id !== (int) $empleado->id_Empleado) {
        abort(404);
    }

    DB::transaction(function () use ($documento) {
        if (!empty($documento->archivo_path) && Storage::disk('public')->exists($documento->archivo_path)) {
            Storage::disk('public')->delete($documento->archivo_path);
        }

        $documento->update([
            'updated_by' => auth()->id(),
        ]);

        $documento->delete();
    });

    return redirect()
        ->route('empleados.edit', ['empleado' => $empleado->id_Empleado, 'tab' => 'documentos'])
        ->with('success', 'Documento eliminado correctamente.');
}
}