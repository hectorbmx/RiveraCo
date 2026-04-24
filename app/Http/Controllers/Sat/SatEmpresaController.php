<?php

namespace App\Http\Controllers\Sat;

use App\Http\Controllers\Controller;
use App\Models\SatEmpresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\SatDocumentRequest;
use App\Models\SatDownloadRequest;
use App\Services\Sat\CsfRequestService;
use App\Jobs\Sat\ProcessSatCsfRequestJob;



class SatEmpresaController extends Controller
{
   public function index()
    {
        $empresas = SatEmpresa::orderBy('nombre')->get();

        $documentRequests = SatDocumentRequest::with(['empresa', 'requester'])
            ->latest()
            ->take(20)
            ->get();

        return view('sat.empresas.index', compact('empresas', 'documentRequests'));
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
                    'sat_password' => ['nullable', 'string', 'max:255'],
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
                if (!empty($data['sat_password'])) {
                        $empresa->sat_password = $data['sat_password'];
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

//    public function storeCsfRequest(SatEmpresa $empresa, CsfRequestService $csfRequestService)
public function storeCsfRequest(SatEmpresa $empresa)
{
    $documentRequest = SatDocumentRequest::create([
        'sat_empresa_id' => $empresa->id,
        'type'           => SatDocumentRequest::TYPE_CSF,
        'status'         => SatDocumentRequest::STATUS_PENDING,
        'requested_by'   => auth()->id(),
    ]);

    \App\Jobs\Sat\ProcessSatCsfRequestJob::dispatch($documentRequest->id);

    return redirect()
        ->route('sat.empresas.index')
        ->with('success', 'Solicitud enviada, en un momento aparecerá el captcha.');
}
// public function submitCaptcha(Request $request, SatDocumentRequest $documentRequest)
// {
//     $data = $request->validate([
//         'captcha_answer' => ['required', 'string', 'max:50'],
//     ]);

//     $documentRequest->update([
//         'captcha_answer' => trim($data['captcha_answer']),
//         'status' => SatDocumentRequest::STATUS_PROCESSING,
//         'error_message' => null,
//     ]);

//     try {
//         \App\Jobs\Sat\ProcessSatCsfRequestJob::dispatchSync($documentRequest->id);
//     } catch (\Throwable $e) {
//         $documentRequest->refresh();

//         return redirect()
//             ->route('sat.empresas.index')
//             ->with('error', $documentRequest->error_message ?: $e->getMessage());
//     }

//     $documentRequest->refresh();

//     // Si falló login/captcha y quedó pendiente sin captcha_path,
//     // volvemos a ejecutar para generar un nuevo captcha en la misma solicitud.
//     if (
//         $documentRequest->status === \App\Models\SatDocumentRequest::STATUS_PENDING
//         && empty($documentRequest->captcha_path)
//     ) {
//         try {
//             \App\Jobs\Sat\ProcessSatCsfRequestJob::dispatchSync($documentRequest->id);
//         } catch (\Throwable $e) {
//             $documentRequest->refresh();

//             return redirect()
//                 ->route('sat.empresas.index')
//                 ->with('error', $documentRequest->error_message ?: $e->getMessage());
//         }
//     }

//     return redirect()
//         ->route('sat.empresas.index')
//         ->with('success', 'Captcha enviado, procesando solicitud.');
// }

public function captchaImage(string $token)
{
    $session = \App\Models\SatCaptchaSession::where('token', $token)
        ->where('expires_at', '>', now())
        ->first();

    if (!$session) {
        return response()->json(['available' => false]);
    }

    return response()->json([
        'available' => true,
        'image'     => $session->image_inline_html, // data:image/png;base64,...
    ]);
}
public function submitCaptcha(Request $request, string $token)
{
    $data = $request->validate([
        'answer' => ['required', 'string', 'max:50'],
    ]);

    $updated = \App\Models\SatCaptchaSession::where('token', $token)
        ->where('expires_at', '>', now())
        ->where('answered', false)
        ->update([
            'answer'   => trim($data['answer']),
            'answered' => true,
        ]);

    if (!$updated) {
        return response()->json(['error' => 'Sesión de captcha no encontrada o expirada'], 404);
    }

    return response()->json(['ok' => true]);
}
public function downloadPdf(SatDocumentRequest $documentRequest)
{
    if (!$documentRequest->file_path || !Storage::exists($documentRequest->file_path)) {
        abort(404);
    }

    return Storage::response($documentRequest->file_path, $documentRequest->file_name);
}
}