<?php

namespace App\Http\Controllers\Sat;

use App\Http\Controllers\Controller;
use App\Models\SatEmpresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SatEmpresaController extends Controller
{
    public function index()
    {
        $empresas = SatEmpresa::orderBy('nombre')->get();

        return view('sat.empresas.index', compact('empresas'));
    }

    public function create()
    {
        return view('sat.empresas.create');
    }

        public function store(Request $request)
{
    $data = $request->validate([
        'nombre' => ['required', 'string', 'max:255'],
        'rfc' => ['required', 'string', 'max:13', 'unique:sat_empresas,rfc'],
        'fiel_password' => ['required', 'string', 'max:255'],
        'cer_file' => ['required', 'file', 'extensions:cer', 'max:5120'],
        'key_file' => ['required', 'file', 'extensions:key', 'max:5120'],
        'activo' => ['nullable', 'boolean'],
    ], [
        'cer_file.mimes' => 'El archivo del certificado debe ser .cer',
        'key_file.mimes' => 'El archivo de la llave debe ser .key',
    ]);

    $rfc = strtoupper(trim($data['rfc']));
    $folder = 'sat/empresas/' . $rfc;

    $cerFile = $request->file('cer_file');
    $keyFile = $request->file('key_file');

    $cerName = 'fiel_' . $rfc . '.cer';
    $keyName = 'fiel_' . $rfc . '.key';

    $cerPath = $cerFile->storeAs($folder, $cerName);
    $keyPath = $keyFile->storeAs($folder, $keyName);

    $fielPassword = $data['fiel_password'];

    if ($request->boolean('fiel_password_has_trailing_space')) {
        $fielPassword .= ' ';
    }

    SatEmpresa::create([
        'nombre' => $data['nombre'],
        'rfc' => $rfc,
        'cer_path' => $cerPath,
        'key_path' => $keyPath,
        'fiel_password' => $fielPassword,
        'activo' => $request->boolean('activo', true),
    ]);

    return redirect()
        ->route('sat.empresas.index')
        ->with('success', 'Empresa SAT creada correctamente.');
}
    public function destroy(SatEmpresa $empresa)
{
    $tieneSolicitudes = \App\Models\SatDownloadRequest::where('sat_empresa_id', $empresa->id)->exists();

    if ($tieneSolicitudes) {
        return back()->with('error', 'No se puede eliminar la empresa porque ya tiene solicitudes SAT relacionadas.');
    }

    if ($empresa->cer_path && Storage::exists($empresa->cer_path)) {
        Storage::delete($empresa->cer_path);
    }

    if ($empresa->key_path && Storage::exists($empresa->key_path)) {
        Storage::delete($empresa->key_path);
    }

    $folder = 'sat/empresas/' . $empresa->rfc;

    if (Storage::exists($folder)) {
        Storage::deleteDirectory($folder);
    }

    $empresa->delete();

    return back()->with('success', 'Empresa SAT eliminada correctamente.');
}

public function edit(SatEmpresa $empresa)
        {
            return view('sat.empresas.edit', compact('empresa'));
        }

        public function update(Request $request, SatEmpresa $empresa)
            {
                $data = $request->validate([
                    'nombre' => ['required', 'string', 'max:255'],
                    'rfc' => ['required', 'string', 'max:13', 'unique:sat_empresas,rfc,' . $empresa->id],
                    'fiel_password' => ['nullable', 'string', 'max:255'],
                    'cer_file' => ['nullable', 'file', 'extensions:cer', 'max:5120'],
                    'key_file' => ['nullable', 'file', 'extensions:key', 'max:5120'],
                    'activo' => ['nullable', 'boolean'],
                ]);

                $rfc = strtoupper(trim($data['rfc']));
                $folder = 'sat/empresas/' . $rfc;

                // 🔹 Password (solo si viene)
                if (!empty($data['fiel_password'])) {
                    $password = $data['fiel_password'];

                    if ($request->boolean('fiel_password_has_trailing_space')) {
                        $password .= ' ';
                    }

                    $empresa->fiel_password = $password;
                }

                // 🔹 Archivos
                if ($request->hasFile('cer_file')) {
                    if ($empresa->cer_path && \Storage::exists($empresa->cer_path)) {
                        \Storage::delete($empresa->cer_path);
                    }

                    $cerName = 'fiel_' . $rfc . '.cer';
                    $empresa->cer_path = $request->file('cer_file')->storeAs($folder, $cerName);
                }

                if ($request->hasFile('key_file')) {
                    if ($empresa->key_path && \Storage::exists($empresa->key_path)) {
                        \Storage::delete($empresa->key_path);
                    }

                    $keyName = 'fiel_' . $rfc . '.key';
                    $empresa->key_path = $request->file('key_file')->storeAs($folder, $keyName);
                }

                $empresa->update([
                    'nombre' => $data['nombre'],
                    'rfc' => $rfc,
                    'activo' => $request->boolean('activo', true),
                ]);

                return redirect()
                    ->route('sat.empresas.index')
                    ->with('success', 'Empresa SAT actualizada correctamente.');
            }
}