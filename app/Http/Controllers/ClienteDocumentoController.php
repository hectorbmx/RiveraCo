<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\ClienteDocumento;
use App\Models\EmpresaDocumentoTipo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ClienteDocumentoController extends Controller
{
    public function store(Request $request, Cliente $cliente)
    {
        $this->authorizeClienteDocumentoAction(['clientes.edit', 'clientes.access']);
        $data = $request->validate([
            'documento_tipo_id' => [
                'required',
                'integer',
                Rule::exists('empresa_documento_tipos', 'id')->where(function ($query) {
                    $query->where('activo', true)
                        ->whereIn('aplica_a', [
                            EmpresaDocumentoTipo::APLICA_CLIENTE,
                            EmpresaDocumentoTipo::APLICA_AMBOS,
                        ]);
                }),
            ],
            'nombre_documento' => ['nullable', 'string', 'max:255'],
            'fecha_documento' => ['nullable', 'date'],
            'fecha_vencimiento' => ['nullable', 'date', 'after_or_equal:fecha_documento'],
            'observaciones' => ['nullable', 'string'],
            'archivo' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ], [
            'documento_tipo_id.required' => 'Selecciona el tipo de documento.',
            'documento_tipo_id.exists' => 'El documento seleccionado no esta disponible para clientes.',
            'fecha_vencimiento.after_or_equal' => 'La fecha de vencimiento no puede ser menor a la fecha del documento.',
            'archivo.required' => 'Debes seleccionar un archivo.',
            'archivo.file' => 'El archivo seleccionado no es valido.',
            'archivo.mimes' => 'El archivo debe ser PDF, JPG, JPEG, PNG o WEBP.',
            'archivo.max' => 'El archivo no debe pesar mas de 10 MB.',
        ]);

        $documentoTipo = EmpresaDocumentoTipo::query()
            ->activos()
            ->aplicaACliente()
            ->findOrFail($data['documento_tipo_id']);

        DB::transaction(function () use ($request, $cliente, $data, $documentoTipo) {
            $file = $request->file('archivo');

            $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
            $originalName = $file->getClientOriginalName();

            $safeTipo = Str::slug($documentoTipo->codigo, '_');
            $filename = $safeTipo . '_' . now()->format('Ymd_His') . '_' . Str::random(8) . '.' . $extension;

            $directory = 'clientes/' . $cliente->id . '/documentos';
            $path = $file->storeAs($directory, $filename, 'public');

            ClienteDocumento::where('cliente_id', $cliente->id)
                ->where('documento_tipo_id', $documentoTipo->id)
                ->where('vigente', true)
                ->update([
                    'vigente' => false,
                    'updated_by' => auth()->id(),
                    'updated_at' => now(),
                ]);

            ClienteDocumento::create([
                'cliente_id' => $cliente->id,
                'documento_tipo_id' => $documentoTipo->id,
                'tipo_documento' => $documentoTipo->codigo,
                'nombre_documento' => $data['nombre_documento'] ?? null,
                'archivo_path' => $path,
                'archivo_nombre_original' => $originalName,
                'mime_type' => $file->getMimeType(),
                'extension' => $extension,
                'tamano_bytes' => $file->getSize(),
                'fecha_documento' => $data['fecha_documento'] ?? null,
                'fecha_vencimiento' => $data['fecha_vencimiento'] ?? null,
                'vigente' => true,
                'estatus_validacion' => 'pendiente',
                'validado_por' => null,
                'validado_en' => null,
                'observaciones' => $data['observaciones'] ?? null,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);
        });

        return redirect()
            ->route('clientes.edit', ['cliente' => $cliente, 'tab' => 'docs'])
            ->with('success', 'Documento del cliente guardado correctamente.');
    }

    public function destroy(Cliente $cliente, ClienteDocumento $documento)
    {
        abort_unless((int) $documento->cliente_id === (int) $cliente->id, 404);
        $this->authorizeClienteDocumentoAction(['clientes.delete', 'clientes.edit', 'clientes.access']);

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
            ->route('clientes.edit', ['cliente' => $cliente, 'tab' => 'docs'])
            ->with('success', 'Documento del cliente eliminado correctamente.');
    }
    private function authorizeClienteDocumentoAction(array $permissions): void
    {
        abort_unless($this->userHasClientePermission($permissions), 403);
    }

    private function userHasClientePermission(array $permissions): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        if (method_exists($user, 'hasRole') && $user->hasRole('super-admin')) {
            return true;
        }

        return $user->getAllPermissions()
            ->pluck('name')
            ->intersect($permissions)
            ->isNotEmpty();
    }
}