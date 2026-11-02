<?php

namespace App\Http\Controllers\Maquinas;

use App\Http\Controllers\Controller;
use App\Models\Maquina;
use App\Models\MaquinaDocumento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class MaquinaDocumentoController extends Controller
{
    public function update(Request $request, Maquina $maquina, MaquinaDocumento $documento): RedirectResponse
    {
        abort_unless((int) $documento->maquina_id === (int) $maquina->getKey(), 404);
        $redirect = redirect()->route('maquinas.show', ['maquina' => $maquina, 'tab' => 'documentacion']);
        $validator = Validator::make($request->all(), [
            'nombre' => ['required', 'string', 'max:255'],
            'tipo' => ['required', 'string', 'max:50'],
            'descripcion' => ['nullable', 'string', 'max:5000'],
        ]);

        if ($validator->fails()) {
            return $redirect->withErrors($validator)->withInput();
        }

        try {
            $documento->update($validator->validated());
        } catch (Throwable $exception) {
            report($exception);
            return $redirect->withErrors(['documento' => 'No se pudieron actualizar los datos del documento.'])->withInput();
        }

        return $redirect->with('success', 'Datos del documento actualizados correctamente.');
    }

    public function destroy(Maquina $maquina, MaquinaDocumento $documento): RedirectResponse
    {
        abort_unless((int) $documento->maquina_id === (int) $maquina->getKey(), 404);
        $redirect = redirect()->route('maquinas.show', ['maquina' => $maquina, 'tab' => 'documentacion']);
        $disk = Storage::disk('local');
        $ruta = $documento->archivo_path;

        try {
            if (! $documento->delete()) {
                throw new RuntimeException('No se pudo eliminar el registro del documento.');
            }
        } catch (Throwable $exception) {
            report($exception);
            return $redirect->withErrors(['documento' => 'No se pudo eliminar el documento. Intenta nuevamente.']);
        }

        try {
            if ($disk->exists($ruta) && ! $disk->delete($ruta)) {
                throw new RuntimeException('No se pudo eliminar el archivo privado: '.$ruta);
            }
        } catch (Throwable $exception) {
            report($exception);
            return $redirect->withErrors(['documento' => 'El registro se eliminó, pero no se pudo borrar el archivo. Contacta al administrador.']);
        }

        return $redirect->with('success', 'Documento eliminado correctamente.');
    }

    public function ver(Maquina $maquina, MaquinaDocumento $documento): StreamedResponse
    {
        return $this->responderArchivo($maquina, $documento, false);
    }

    public function descargar(Maquina $maquina, MaquinaDocumento $documento): StreamedResponse
    {
        return $this->responderArchivo($maquina, $documento, true);
    }

    private function responderArchivo(Maquina $maquina, MaquinaDocumento $documento, bool $descargar): StreamedResponse
    {
        abort_unless((int) $documento->maquina_id === (int) $maquina->getKey(), 404);
        $disk = Storage::disk('local');
        abort_unless($disk->exists($documento->archivo_path), 404);

        $mime = in_array($documento->mime_type, ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'], true)
            ? $documento->mime_type : 'application/octet-stream';
        $headers = [
            'Content-Type' => $mime,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ];

        return $descargar
            ? $disk->download($documento->archivo_path, $documento->nombre_original, $headers)
            : $disk->response($documento->archivo_path, $documento->nombre_original, $headers);
    }

    public function store(Request $request, Maquina $maquina): RedirectResponse
    {
        $redirect = redirect()->route('maquinas.show', ['maquina' => $maquina, 'tab' => 'documentacion']);
        $validator = Validator::make($request->all(), [
            'nombre' => ['required', 'string', 'max:255'],
            'tipo' => ['required', 'string', 'max:50'],
            'descripcion' => ['nullable', 'string', 'max:5000'],
            'archivo' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ], [
            'archivo.required' => 'Selecciona un documento.',
            'archivo.mimes' => 'El archivo debe ser PDF, JPG, PNG o WebP.',
            'archivo.max' => 'El archivo no debe superar los 10 MB.',
        ]);

        if ($validator->fails()) {
            return $redirect->withErrors($validator)->withInput($request->except('archivo'));
        }

        $data = $validator->validated();
        $archivo = $request->file('archivo');
        $disk = Storage::disk('local');
        $ruta = null;

        try {
            $ruta = $archivo->store('maquinas/'.$maquina->getKey().'/documentos', 'local');
            if (! is_string($ruta) || $ruta === '') {
                throw new RuntimeException('No se pudo guardar el archivo.');
            }

            $maquina->documentos()->create([
                'nombre' => $data['nombre'],
                'tipo' => $data['tipo'],
                'descripcion' => $data['descripcion'] ?? null,
                'nombre_original' => mb_substr($archivo->getClientOriginalName(), 0, 255),
                'archivo_path' => $ruta,
                'mime_type' => $archivo->getMimeType(),
                'tamano' => $archivo->getSize(),
                'uploaded_by' => $request->user()->getAuthIdentifier(),
            ]);
        } catch (Throwable $exception) {
            if (is_string($ruta) && $ruta !== '' && ! $disk->delete($ruta)) {
                report(new RuntimeException('No se pudo limpiar el archivo después de un error de carga.'));
            }
            report($exception);

            return $redirect->withErrors(['archivo' => 'No se pudo guardar el documento. Intenta nuevamente.'])
                ->withInput($request->except('archivo'));
        }

        return $redirect->with('success', 'Documento de la máquina guardado correctamente.');
    }
}
