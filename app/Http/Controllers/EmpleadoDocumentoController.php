<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\EmpleadoDocumento;
use App\Models\EmpresaDocumentoTipo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class EmpleadoDocumentoController extends Controller
{
    public function descargarSeleccion(Request $request, Empleado $empleado)
    {
        $data = $request->validate([
            'documentos' => ['required', 'array', 'min:1'],
            'documentos.*' => ['required', 'integer', 'distinct'],
        ], ['documentos.required' => 'Selecciona al menos un documento para descargar.']);

        if (! class_exists(\ZipArchive::class)) {
            return back()->withErrors(['documentos' => 'El servidor no tiene habilitada la extensión ZIP.']);
        }

        $documentos = EmpleadoDocumento::query()->where('empleado_id', $empleado->id_Empleado)
            ->whereIn('id', $data['documentos'])->orderBy('id')->get();
        if ($documentos->count() !== count($data['documentos'])) {
            abort(404);
        }

        $disco = Storage::disk('public');
        $base = realpath($disco->path('empleados/'.$empleado->id_Empleado.'/documentos'));
        $archivos = [];
        foreach ($documentos as $documento) {
            $ruta = $documento->archivo_path ? realpath($disco->path($documento->archivo_path)) : false;
            if (! $base || ! $ruta || ! str_starts_with($ruta, $base.DIRECTORY_SEPARATOR) || ! is_file($ruta) || ! is_readable($ruta)) {
                return back()->withErrors(['documentos' => 'No se encontró uno de los archivos seleccionados. Revisa el expediente antes de descargar.']);
            }
            $extension = strtolower(pathinfo($ruta, PATHINFO_EXTENSION));
            $nombre = Str::slug($documento->nombre_mostrar ?: 'documento', '_') ?: 'documento';
            $archivos[] = [$ruta, $nombre.'_'.$documento->id.($extension !== '' ? '.'.$extension : '')];
        }

        $temporal = tempnam(sys_get_temp_dir(), 'expediente_');
        if ($temporal === false) {
            return back()->withErrors(['documentos' => 'No fue posible preparar el archivo ZIP.']);
        }
        $zip = new \ZipArchive();
        try {
            if ($zip->open($temporal, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('No se pudo abrir el archivo ZIP.');
            }
            foreach ($archivos as [$ruta, $nombre]) {
                if (! $zip->addFile($ruta, $nombre)) {
                    throw new \RuntimeException('No se pudo agregar un archivo al ZIP.');
                }
            }
            if (! $zip->close()) {
                throw new \RuntimeException('No se pudo terminar el archivo ZIP.');
            }
        } catch (\Throwable $e) {
            @$zip->close();
            @unlink($temporal);
            report($e);
            return back()->withErrors(['documentos' => 'No fue posible generar el archivo ZIP.']);
        }

        return response()->download($temporal, 'documentos_empleado_'.$empleado->id_Empleado.'.zip', ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend(true);
    }

    public function store(Request $request, Empleado $empleado)
    {
        $data = $request->validate([
            // 'tipo_documento'     => ['required', 'in:' . implode(',', EmpleadoDocumento::TIPOS)],
            'documento_tipo_id' => [
                'required',
                Rule::exists('empresa_documento_tipos', 'id')
                    ->where('activo', true)
                    ->whereIn('aplica_a', [
                        EmpresaDocumentoTipo::APLICA_EMPLEADO,
                        EmpresaDocumentoTipo::APLICA_AMBOS,
                    ]),
            ],
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
      $documentoTipo = EmpresaDocumentoTipo::query()
          ->activos()
          ->aplicaAEmpleado()
          ->findOrFail($data['documento_tipo_id']);

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
